<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_login_with_valid_credentials_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'arief@amorecoffee.id',
            'password' => 'AmoreOwner2026!',
            'device_name' => 'Chrome MacOS',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'subscription_status'],
                    'workspaces' => [
                        '*' => ['workspace_id', 'workspace_name', 'workspace_slug', 'role'],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertEquals('arief@amorecoffee.id', $response->json('data.user.email'));
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'arief@amorecoffee.id',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_fetch_profile_and_accessible_workspaces(): void
    {
        /** @var User $user */
        $user = User::where('email', 'arief@amorecoffee.id')->first();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.email', 'arief@amorecoffee.id')
            ->assertJsonPath('data.workspaces.0.workspace_slug', 'amore-coffee')
            ->assertJsonPath('data.workspaces.0.role', 'OWNER');
    }

    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        /** @var User $user */
        $user = User::where('email', 'arief@amorecoffee.id')->first();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logout berhasil. Sesi telah diakhiri.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test_token',
        ]);
    }

    public function test_forgot_password_sends_notification_and_stores_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'arief@amorecoffee.id',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Tautan pemulihan kata sandi telah dikirim ke alamat email Anda.']);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'arief@amorecoffee.id',
        ]);

        $user = User::where('email', 'arief@amorecoffee.id')->first();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_with_valid_token_updates_password(): void
    {
        $plainToken = 'valid-reset-token-1234567890';
        DB::table('password_reset_tokens')->insert([
            'email' => 'arief@amorecoffee.id',
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'arief@amorecoffee.id',
            'token' => $plainToken,
            'password' => 'NewSecretPassword2026!',
            'password_confirmation' => 'NewSecretPassword2026!',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Kata sandi berhasil diperbarui. Silakan login kembali.']);

        /** @var User $updatedUser */
        $updatedUser = User::where('email', 'arief@amorecoffee.id')->first();
        $this->assertTrue(Hash::check('NewSecretPassword2026!', $updatedUser->password));

        // Token harus terhapus setelah reset kata sandi berhasil
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'arief@amorecoffee.id',
        ]);
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => 'arief@amorecoffee.id',
            'token' => Hash::make('real-token'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'arief@amorecoffee.id',
            'token' => 'wrong-token',
            'password' => 'NewSecretPassword2026!',
            'password_confirmation' => 'NewSecretPassword2026!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }
}
