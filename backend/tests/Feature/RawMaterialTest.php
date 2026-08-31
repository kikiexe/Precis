<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RawMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_create_raw_materials(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Owner Test',
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
        ]);

        $workspace = Workspace::create([
            'id' => (string) Str::uuid(),
            'name' => 'Workspace Test',
            'slug' => 'workspace-test',
            'owner_user_id' => $user->id,
        ]);

        WorkspaceMember::create([
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'OWNER',
            'job_title' => 'Owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Workspace-Id' => $workspace->id])
            ->getJson('/api/v1/raw-materials');

        $response->assertStatus(200);

        $createRes = $this->actingAs($user)
            ->withHeaders(['X-Workspace-Id' => $workspace->id])
            ->postJson('/api/v1/raw-materials', [
                'name' => 'Susu Fresh Milk 1L',
                'current_stock' => 10,
                'min_stock_alert' => 3,
                'unit' => 'liter',
            ]);

        $createRes->assertStatus(201);
    }
}
