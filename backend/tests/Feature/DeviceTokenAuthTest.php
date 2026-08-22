<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PosTerminal;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_valid_device_token_grants_access_and_returns_terminal_info(): void
    {
        // token yang di-seed di BranchSeeder: 'pos-device-token-sleman-01'
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-sleman-01')
            ->getJson('/api/v1/pos/terminal-info');

        $response->assertOk()
            ->assertJsonStructure([
                'terminal_id',
                'terminal_name',
                'workspace_id',
                'branch_id',
                'branch_name',
            ])
            ->assertJsonPath('terminal_name', 'Amore POS Tab Sleman #01');
    }

    public function test_missing_device_token_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/pos/terminal-info');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Header X-Device-Token wajib disertakan untuk terminal POS.']);
    }

    public function test_invalid_device_token_returns_unauthorized(): void
    {
        $response = $this->withHeader('X-Device-Token', 'invalid-token-xyz')
            ->getJson('/api/v1/pos/terminal-info');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Device token terminal POS tidak valid atau tidak terdaftar.']);
    }

    public function test_inactive_terminal_returns_forbidden(): void
    {
        $terminal = PosTerminal::withoutGlobalScopes()->where('terminal_name', 'like', '%Sleman%')->first();
        $terminal->update(['is_active' => false]);

        $response = $this->withHeader('X-Device-Token', 'pos-device-token-sleman-01')
            ->getJson('/api/v1/pos/terminal-info');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Terminal POS ini telah dinonaktifkan oleh administrator.']);
    }
}
