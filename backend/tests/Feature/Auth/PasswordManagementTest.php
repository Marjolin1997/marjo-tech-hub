<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_response_does_not_reveal_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $existing = $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk()->json('message');
        $missing = $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])->assertOk()->json('message');

        $this->assertSame($existing, $missing);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_notification_targets_frontend_route(): void
    {
        Notification::fake();
        config()->set('app.frontend_url', 'https://app.example.test');
        $user = User::factory()->create();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;
            $parts = parse_url($url);
            parse_str($parts['query'] ?? '', $query);

            return ($parts['scheme'] ?? null) === 'https'
                && ($parts['host'] ?? null) === 'app.example.test'
                && ($parts['path'] ?? null) === '/reset-password'
                && ($query['email'] ?? null) === $user->email
                && filled($query['token'] ?? null);
        });
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);

        $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertUnprocessable();

        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
    }

    public function test_authenticated_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPassword123']);

        $this->actingAs($user)->putJson('/api/auth/password', [
            'current_password' => 'CurrentPassword123',
            'password' => 'ChangedPassword456',
            'password_confirmation' => 'ChangedPassword456',
        ])->assertOk();

        $this->assertTrue(Hash::check('ChangedPassword456', $user->fresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPassword123']);

        $this->actingAs($user)->putJson('/api/auth/password', [
            'current_password' => 'WrongPassword123',
            'password' => 'ChangedPassword456',
            'password_confirmation' => 'ChangedPassword456',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('CurrentPassword123', $user->fresh()->password));
    }
}
