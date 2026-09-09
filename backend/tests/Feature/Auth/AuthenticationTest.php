<?php

namespace Tests\Feature\Auth;

use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\LoginVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_current_user(): void { $this->getJson('/api/auth/me')->assertUnauthorized(); }

    public function test_valid_password_requires_email_two_factor_before_session_is_created(): void
    {
        Notification::fake(); $user=User::factory()->create(['password'=>'correct-password']);
        $response=$this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'correct-password','remember'=>true])
            ->assertOk()->assertJsonPath('requires_2fa',true)->assertJsonStructure(['challenge_id']);
        $this->assertGuest(); $this->getJson('/api/auth/me')->assertUnauthorized();
        $challenge=LoginChallenge::findOrFail($response->json('challenge_id'));
        $this->assertTrue($challenge->remember); $this->assertFalse($challenge->expires_at->isPast());
        Notification::assertSentTo($user,LoginVerificationCode::class);
    }

    public function test_user_can_verify_two_factor_code_then_read_session_identity_and_sign_out(): void
    {
        $user=User::factory()->create(['password'=>'correct-password']); $code='482913';
        $challenge=LoginChallenge::create(['user_id'=>$user->id,'code_hash'=>Hash::make($code),'remember'=>true,'expires_at'=>now()->addMinutes(10)]);
        $this->postJson('/api/auth/verify-2fa',['challenge_id'=>$challenge->id,'code'=>$code])->assertOk()->assertJsonPath('user.email',$user->email);
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.id',$user->id);
        $this->postJson('/api/auth/logout')->assertOk(); $this->app['auth']->forgetGuards(); $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_invalid_credentials_do_not_create_a_challenge_or_session(): void
    {
        $user=User::factory()->create(['password'=>'correct-password']);
        $this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'wrong-password'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest(); $this->assertDatabaseCount('login_challenges',0);
    }

    public function test_incorrect_two_factor_code_does_not_create_session(): void
    {
        $user=User::factory()->create(); $challenge=LoginChallenge::create(['user_id'=>$user->id,'code_hash'=>Hash::make('123456'),'expires_at'=>now()->addMinutes(10)]);
        $this->postJson('/api/auth/verify-2fa',['challenge_id'=>$challenge->id,'code'=>'654321'])->assertUnprocessable();
        $this->assertGuest(); $this->assertSame(1,$challenge->fresh()->attempts);
    }

    public function test_expired_two_factor_code_is_rejected(): void
    {
        $user=User::factory()->create(); $challenge=LoginChallenge::create(['user_id'=>$user->id,'code_hash'=>Hash::make('123456'),'expires_at'=>now()->subMinute()]);
        $this->postJson('/api/auth/verify-2fa',['challenge_id'=>$challenge->id,'code'=>'123456'])->assertUnprocessable(); $this->assertGuest();
    }
}
