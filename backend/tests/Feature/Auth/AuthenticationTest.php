<?php

namespace Tests\Feature\Auth;

use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\LoginVerificationCode;
use App\TwoFactorChannel;
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
            ->assertOk()->assertJsonPath('requires_2fa',true)->assertJsonPath('channel','email')->assertJsonStructure(['challenge_id','destination']);
        $this->assertGuest(); $this->getJson('/api/auth/me')->assertUnauthorized();
        $challenge=LoginChallenge::findOrFail($response->json('challenge_id'));
        $this->assertTrue($challenge->remember); $this->assertSame(TwoFactorChannel::Email,$challenge->channel); $this->assertFalse($challenge->expires_at->isPast());
        Notification::assertSentTo($user,LoginVerificationCode::class);
    }

    public function test_phone_channel_requires_a_verified_phone(): void
    {
        Notification::fake(); $user=User::factory()->create(['password'=>'correct-password','phone_number'=>'+355690000000','phone_verified_at'=>null]);
        $this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'correct-password','channel'=>'sms'])
            ->assertUnprocessable()->assertJsonPath('message','Verify a phone number in your profile before using SMS or WhatsApp.');
        $this->assertDatabaseCount('login_challenges',0); Notification::assertNothingSent();
    }

    public function test_unconfigured_phone_provider_fails_closed_without_leaving_a_challenge(): void
    {
        $user=User::factory()->create(['password'=>'correct-password','phone_number'=>'+355690000000','phone_verified_at'=>now()]);
        $this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'correct-password','channel'=>'whatsapp'])
            ->assertStatus(503)->assertJsonPath('message','WhatsApp verification is not configured yet. Choose email for now.');
        $this->assertDatabaseCount('login_challenges',0); $this->assertGuest();
    }

    public function test_user_can_verify_two_factor_code_then_read_session_identity_and_sign_out(): void
    {
        $user=User::factory()->create(['password'=>'correct-password']); $code='482913';
        $challenge=LoginChallenge::create(['user_id'=>$user->id,'code_hash'=>Hash::make($code),'remember'=>true,'expires_at'=>now()->addMinutes(10)]);
        $this->postJson('/api/auth/verify-2fa',['challenge_id'=>$challenge->id,'code'=>$code])->assertOk()->assertJsonPath('user.email',$user->email)->assertJsonPath('user.roles', [])->assertJsonPath('user.permissions', []);
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.id',$user->id)->assertJsonPath('user.roles', [])->assertJsonPath('user.permissions', []);
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
