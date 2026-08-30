<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Media\PresignUploadRequest;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController
{
    private const MAX_MOCK_UPLOAD_BYTES = 2097152; // batas 2MB
    private const ALLOWED_MOCK_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'pdf'];

    public function __construct(
        private readonly MediaStorageService $mediaStorageService,
    ) {
    }

    /**
     * generate URL presigned upload untuk upload langsung dari browser ke object storage
     */
    public function presignUpload(PresignUploadRequest $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $result = $this->mediaStorageService->generatePresignedUpload(
            workspaceId: $workspaceId,
            filename: $request->validated('filename'),
            mimeType: $request->validated('mime_type'),
            sizeBytes: (int) $request->validated('size_bytes'),
        );

        return new JsonResponse([
            'message' => 'URL presigned upload berhasil dibuat.',
            'data' => $result,
        ], Response::HTTP_OK);
    }

    /**
     * terima upload biner file ke disk storage lokal (environment lokal / testing)
     */
    public function mockUpload(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        if (! app()->environment('local', 'testing')) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $key = (string) $request->query('key');
        if (empty($key)) {
            return new JsonResponse([
                'message' => 'Parameter query key wajib disertakan.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // cegah directory traversal dengan strict rejection
        if (str_contains($key, '..') || str_contains($key, '\\') || str_contains($key, "\0")) {
            return new JsonResponse([
                'message' => 'Karakter path tidak valid.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $cleanKey = ltrim($key, '/');

        // batasi lokasi penulisan hanya pada folder staging
        if (! str_starts_with($cleanKey, 'staging/')) {
            return new JsonResponse([
                'message' => 'Hanya direktori staging yang diizinkan untuk mock upload.',
            ], Response::HTTP_FORBIDDEN);
        }

        // validasi whitelist ekstensi file
        $extension = strtolower(pathinfo($cleanKey, PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_MOCK_EXTENSIONS, true)) {
            return new JsonResponse([
                'message' => 'Ekstensi file tidak didukung.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // validasi ukuran biner maksimal 2MB
        $content = $request->getContent();
        if (strlen($content) > self::MAX_MOCK_UPLOAD_BYTES) {
            return new JsonResponse([
                'message' => 'Ukuran file melebihi batas maksimum 2MB.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Storage::disk('public')->put($cleanKey, $content);

        return response('', Response::HTTP_OK);
    }
}
