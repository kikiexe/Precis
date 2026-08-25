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
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-seturan-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'kiki@gmail.com',
                'password' => '123456',
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
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-seturan-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'paundra@gmail.com',
                'password' => '123456',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Otorisasi master berhasil. Kiosk unlocked.');
    }

    public function test_invalid_password_fails_master_unlock(): void
    {
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-seturan-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'kiki@gmail.com',
                'password' => 'WrongPassword!',
            ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    public function test_staff_role_credentials_cannot_unlock_pos_kiosk(): void
    {
        // sita memiliki peran STAFF ga bisa melakukan master unlock
        $response = $this->withHeader('X-Device-Token', 'pos-device-token-seturan-01')
            ->postJson('/api/v1/pos/master-unlock', [
                'email' => 'ami@gmail.com',
                'password' => '123456',
            ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }
}
