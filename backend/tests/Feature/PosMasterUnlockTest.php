<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosMasterUnlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_valid_owner_password_unlocks_pos_kiosk(): void
    {
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-sleman-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'arief@amorecoffee.id',
                'password' => 'AmoreOwner2026!',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Otorisasi master berhasil. Kiosk unlocked.')
            ->assertJsonStructure([
                'data' => [
                    'unlocked_at',
                    'terminal_id',
                    'branch_id',
                    'workspace_id',
                ],
            ]);
    }

    public function test_valid_admin_password_unlocks_pos_kiosk(): void
    {
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-sleman-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'budi.manager@amorecoffee.id',
                'password' => 'BudiManager2026!',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Otorisasi master berhasil. Kiosk unlocked.');
    }

    public function test_invalid_password_fails_master_unlock(): void
    {
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-sleman-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'arief@amorecoffee.id',
                'password' => 'WrongPassword!',
            ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    public function test_staff_role_credentials_cannot_unlock_pos_kiosk(): void
    {
        // Siti memiliki peran STAFF (tidak diizinkan melakukan master unlock)
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-sleman-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'siti.kasir@amorecoffee.id',
                'password' => 'SitiKasir2026!',
            ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }
}
