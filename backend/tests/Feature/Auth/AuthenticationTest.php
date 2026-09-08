<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_current_user(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_user_can_sign_in_and_read_session_identity(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'remember' => true,
        ])->assertOk()->assertJsonPath('user.email', $user->email);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_invalid_credentials_do_not_create_a_session(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertGuest();
    }
}
