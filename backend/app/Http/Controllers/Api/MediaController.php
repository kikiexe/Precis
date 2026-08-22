<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Media\PresignUploadRequest;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MediaController
{
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
}
