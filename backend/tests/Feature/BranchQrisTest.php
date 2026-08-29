<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchQrisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_owner_can_update_and_clear_branch_qris_image_url(): void
    {
        $user = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->firstOrFail();

        $token = $user->createToken('test-token')->plainTextToken;

        // 1. Update QRIS image URL
        $qrisUrl = 'https://assets.precis.com/norde-coffee/qris/seturan-qris-official.webp';
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson('/api/v1/branches/' . $branch->id, [
                'name' => $branch->name,
                'qris_image_url' => $qrisUrl,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.qris_image_url', $qrisUrl);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'qris_image_url' => $qrisUrl,
        ]);

        // 2. Clear QRIS image URL (set null)
        $clearResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson('/api/v1/branches/' . $branch->id, [
                'qris_image_url' => null,
            ]);

        $clearResponse->assertOk()
            ->assertJsonPath('data.qris_image_url', null);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'qris_image_url' => null,
        ]);
    }

    public function test_pos_terminal_info_includes_branch_qris_image_url(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->firstOrFail();
        $qrisUrl = 'https://assets.precis.com/norde-coffee/qris/seturan-qris.webp';

        $branch->update(['qris_image_url' => $qrisUrl]);

        $response = $this->withHeader('X-Device-Token', 'pos-device-token-seturan-01')
            ->getJson('/api/v1/pos/terminal-info');

        $response->assertOk()
            ->assertJsonPath('qris_image_url', $qrisUrl)
            ->assertJsonPath('branch_name', $branch->name);
    }
}
