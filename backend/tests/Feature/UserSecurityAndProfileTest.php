<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSecurityAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_name(): void
    {
        /** @var User $user */
        $user = User::create([
            'name' => 'Nama Lama',
            'email' => 'staff@example.com',
            'password' => Hash::make('OldPassword123!'),
            'subscription_status' => 'TRIAL',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/profile', [
            'name' => 'Nama Baru Staff',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profil berhasil diperbarui.',
                'data' => [
                    'name' => 'Nama Baru Staff',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru Staff',
        ]);
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        /** @var User $user */
        $user = User::create([
            'name' => 'Staff Barista',
            'email' => 'barista@example.com',
            'password' => Hash::make('OldPassword123!'),
            'subscription_status' => 'TRIAL',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Kata sandi berhasil diperbarui.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword456!', $user->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password(): void
    {
        /** @var User $user */
        $user = User::create([
            'name' => 'Staff Barista',
            'email' => 'barista2@example.com',
            'password' => Hash::make('OldPassword123!'),
            'subscription_status' => 'TRIAL',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'WrongPassword999!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }
}
