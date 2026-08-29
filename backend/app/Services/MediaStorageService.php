<?php

declare(strict_types=1);

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaStorageService
{
    private const MAX_SIZE_BYTES = 2097152; // 2MB
    private const ALLOWED_MIME_TYPES = [
        'image/webp',
        'image/jpeg',
        'image/png',
    ];

    /**
     * generate URL presigned upload untuk upload langsung ke object storage
     *
     * @return array{upload_url: string, key: string, public_url: string, expires_in_seconds: int}
     */
    public function generatePresignedUpload(
        string $workspaceId,
        string $filename,
        string $mimeType,
        int $sizeBytes,
        int $expiryMinutes = 5
    ): array {
        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'mime_type' => ['Tipe file tidak didukung. Format yang diizinkan: WebP, JPEG, PNG.'],
            ]);
        }

        if ($sizeBytes > self::MAX_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'size_bytes' => ['Ukuran file melebihi batas maksimum 2MB.'],
            ]);
        }

        $extension = match ($mimeType) {
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'bin',
        };

        $uniqueId = (string) Str::uuid();
        $key = sprintf('staging/%s/%s.%s', $workspaceId, $uniqueId, $extension);
        $expiresInSeconds = $expiryMinutes * 60;

        $hasR2Config = ! empty(config('filesystems.disks.r2.key')) && ! empty(config('filesystems.disks.r2.secret'));
        $useR2 = config('filesystems.default') === 'r2' && $hasR2Config && ! app()->runningUnitTests();

        if ($useR2) {
            /** @var S3Client $client */
            $client = Storage::disk('r2')->getClient();
            $command = $client->getCommand('PutObject', [
                'Bucket' => config('filesystems.disks.r2.bucket'),
                'Key' => $key,
                'ContentType' => $mimeType,
            ]);

            $presignedRequest = $client->createPresignedRequest($command, "+{$expiryMinutes} minutes");
            $uploadUrl = (string) $presignedRequest->getUri();
            $publicUrl = rtrim((string) config('filesystems.disks.r2.url'), '/') . '/' . $key;
        } else {
            // URL simulasi untuk environment lokal atau testing
            $uploadUrl = url("/api/v1/media/mock-upload?key=" . urlencode($key));
            $publicUrl = url("/storage/" . $key);
        }

        return [
            'upload_url' => $uploadUrl,
            'key' => $key,
            'public_url' => $publicUrl,
            'expires_in_seconds' => $expiresInSeconds,
        ];
    }

    /**
     * pindahkan file foto dari folder upload sementara (staging) ke folder penyimpanan permanen
     */
    public function moveToPermanent(string $stagingPathOrUrl, string $workspaceId): string
    {
        // ekstrak path file jika input berupa URL lengkap
        $path = parse_url($stagingPathOrUrl, PHP_URL_PATH) ?? $stagingPathOrUrl;
        $path = ltrim($path, '/');

        // jika file foto memang sudah ada di folder permanen, tidak perlu dipindahkan lagi
        if (str_contains($path, 'permanent/')) {
            return $stagingPathOrUrl;
        }

        $filename = basename($path);
        $permanentKey = sprintf('permanent/%s/%s', $workspaceId, $filename);

        $hasR2Config = ! empty(config('filesystems.disks.r2.key')) && ! empty(config('filesystems.disks.r2.secret'));
        $useR2 = config('filesystems.default') === 'r2' && $hasR2Config && ! app()->runningUnitTests();
        $disk = $useR2 ? 'r2' : 'public';

        // pindahkan file dari folder sementara ke folder permanen jika ada
        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->move($path, $permanentKey);
            }
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal memindahkan berkas media dari staging ke permanent storage: ' . $e->getMessage(), [
                'path' => $path,
                'permanent_key' => $permanentKey,
                'disk' => $disk,
            ]);
        }

        if ($useR2 && config('filesystems.disks.r2.url')) {
            return rtrim((string) config('filesystems.disks.r2.url'), '/') . '/' . $permanentKey;
        }

        return url('/storage/' . $permanentKey);
    }
}
