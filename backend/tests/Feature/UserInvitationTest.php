<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['access_control.owner_email' => null]);
        $this->seed(AccessControlSeeder::class);
        Notification::fake();
    }

    private function actor(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function invitation(array $overrides = []): array
    {
        $token = Str::random(64);
        $invitation = UserInvitation::query()->create(array_merge([
            'email' => 'invitee@example.com', 'name' => 'Invitee User', 'token_hash' => hash('sha256', $token),
            'roles' => ['Viewer'], 'invited_by' => $this->actor('Owner')->id, 'expires_at' => now()->addDay(),
        ], $overrides));
        return [$invitation, $token];
    }

    public function test_guest_cannot_manage_invitations(): void
    {
        $this->getJson('/api/access-control/invitations')->assertUnauthorized();
        $this->postJson('/api/access-control/invitations', [])->assertUnauthorized();
    }

    public function test_admin_can_invite_non_owner_and_notification_does_not_persist_plain_token(): void
    {
        $admin = $this->actor('Admin');
        $response = $this->actingAs($admin)->postJson('/api/access-control/invitations', [
            'name' => 'New Editor', 'email' => 'NEW.EDITOR@example.com', 'roles' => ['Editor'],
        ])->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.email', 'new.editor@example.com');

        $invitation = UserInvitation::query()->findOrFail($response->json('data.id'));
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertSame(['Editor'], $invitation->roles);
        Notification::assertSentOnDemand(UserInvitationNotification::class);
    }

    public function test_admin_cannot_invite_owner_but_owner_can(): void
    {
        $payload = ['name' => 'Owner Candidate', 'email' => 'owner.candidate@example.com', 'roles' => ['Owner']];
        $this->actingAs($this->actor('Admin'))->postJson('/api/access-control/invitations', $payload)->assertForbidden();
        $this->actingAs($this->actor('Owner'))->postJson('/api/access-control/invitations', $payload)->assertCreated();
    }

    public function test_unknown_role_and_existing_user_email_are_rejected(): void
    {
        $admin = $this->actor('Admin');
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        $this->actingAs($admin)->postJson('/api/access-control/invitations', ['name' => 'Bad', 'email' => 'bad@example.com', 'roles' => ['SuperAdmin']])->assertUnprocessable();
        $this->actingAs($admin)->postJson('/api/access-control/invitations', ['name' => 'Existing', 'email' => $existing->email, 'roles' => ['Viewer']])->assertUnprocessable();
    }

    public function test_valid_invitation_can_be_inspected_and_accepted_with_assigned_role(): void
    {
        [$invitation, $token] = $this->invitation();
        $this->getJson('/api/invitations/accept?email='.urlencode($invitation->email).'&token='.urlencode($token))
            ->assertOk()->assertJsonPath('data.roles.0', 'Viewer');

        $this->postJson('/api/invitations/accept', [
            'email' => $invitation->email, 'token' => $token,
            'password' => 'StrongPass123', 'password_confirmation' => 'StrongPass123',
        ])->assertOk();

        $user = User::query()->where('email', $invitation->email)->firstOrFail();
        $this->assertTrue($user->hasRole('Viewer'));
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->postJson('/api/invitations/accept', ['email' => $invitation->email, 'token' => $token, 'password' => 'StrongPass123', 'password_confirmation' => 'StrongPass123'])->assertUnprocessable();
    }

    public function test_invalid_expired_and_revoked_tokens_are_rejected(): void
    {
        [$valid, $token] = $this->invitation(['email' => 'invalid@example.com']);
        $this->getJson('/api/invitations/accept?email='.urlencode($valid->email).'&token=wrong')->assertUnprocessable();

        [$expired, $expiredToken] = $this->invitation(['email' => 'expired@example.com', 'expires_at' => now()->subMinute()]);
        $this->getJson('/api/invitations/accept?email='.urlencode($expired->email).'&token='.urlencode($expiredToken))->assertUnprocessable();

        [$revoked, $revokedToken] = $this->invitation(['email' => 'revoked@example.com', 'revoked_at' => now()]);
        $this->getJson('/api/invitations/accept?email='.urlencode($revoked->email).'&token='.urlencode($revokedToken))->assertUnprocessable();
    }

    public function test_admin_can_resend_and_revoke_non_owner_invitation(): void
    {
        [$invitation] = $this->invitation(['email' => 'lifecycle@example.com']);
        $admin = $this->actor('Admin');
        $oldHash = $invitation->token_hash;
        $this->actingAs($admin)->postJson("/api/access-control/invitations/{$invitation->id}/resend")->assertOk();
        $this->assertNotSame($oldHash, $invitation->fresh()->token_hash);
        $this->actingAs($admin)->deleteJson("/api/access-control/invitations/{$invitation->id}")->assertOk()->assertJsonPath('data.status', 'revoked');
    }
}
