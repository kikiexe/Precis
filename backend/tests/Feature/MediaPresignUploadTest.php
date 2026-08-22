<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaPresignUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_request_presigned_upload_url_for_webp_image(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/media/presign-upload', [
                'filename' => 'selfie_attendance.webp',
                'mime_type' => 'image/webp',
                'size_bytes' => 150000,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'upload_url',
                    'key',
                    'public_url',
                    'expires_in_seconds',
                ],
            ]);

        $this->assertStringContainsString('staging/' . $workspace->id, $response->json('data.key'));
    }

    public function test_presign_upload_rejects_unsupported_mime_type(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/media/presign-upload', [
                'filename' => 'document.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 100000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mime_type']);
    }

    public function test_presign_upload_rejects_files_larger_than_2mb(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/media/presign-upload', [
                'filename' => 'heavy_image.webp',
                'mime_type' => 'image/webp',
                'size_bytes' => 3000000, // 3MB (melebihi 2MB)
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['size_bytes']);
    }
}
