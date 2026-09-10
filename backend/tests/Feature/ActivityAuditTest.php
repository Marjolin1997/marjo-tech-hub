<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['access_control.owner_email' => null]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(AccessControlSeeder::class);
    }

    private function role(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_only_authorized_roles_can_view_activity(): void
    {
        $admin = $this->role('Admin');
        $owner = $this->role('Owner');
        $editor = $this->role('Editor');
        $viewer = $this->role('Viewer');

        $this->actingAs($admin)->getJson('/api/activity')->assertOk();
        $this->actingAs($owner)->getJson('/api/activity')->assertOk();
        $this->actingAs($editor)->getJson('/api/activity')->assertForbidden();
        $this->actingAs($viewer)->getJson('/api/activity')->assertForbidden();
    }

    public function test_role_change_creates_a_safe_audit_event(): void
    {
        Notification::fake();
        $admin = $this->role('Admin');
        $target = $this->role('Viewer');

        $this->actingAs($admin)->putJson("/api/access-control/users/{$target->id}/roles", ['roles' => ['Editor']])->assertOk();

        $event = AuditEvent::query()->where('action', 'access.roles_updated')->sole();
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame($target->id, $event->target_user_id);
        $this->assertSame(['Editor'], $event->metadata['added_roles']);
        $this->assertSame(['Viewer'], $event->metadata['removed_roles']);
    }

    public function test_logger_drops_metadata_that_is_not_explicitly_allowlisted(): void
    {
        $actor = $this->role('Admin');
        $event = app(AuditLogger::class)->record($actor, 'test.redaction', 'test', 10, 'Safe label', [
            'status' => 'ok',
            'password' => 'never-store-this',
            'token' => 'never-store-this',
            'path' => '/private/secret',
            'content' => 'private body',
        ]);

        $this->assertSame(['status' => 'ok'], $event->metadata);
        $encoded = json_encode($event->toArray());
        $this->assertStringNotContainsString('never-store-this', $encoded);
        $this->assertStringNotContainsString('/private/secret', $encoded);
        $this->assertStringNotContainsString('private body', $encoded);
    }

    public function test_activity_filters_are_bounded_and_deterministic(): void
    {
        $admin = $this->role('Admin');
        $logger = app(AuditLogger::class);
        $logger->record($admin, 'entry.created', 'entry', 1, 'One');
        $logger->record($admin, 'category.created', 'category', 2, 'Two');

        $this->actingAs($admin)->getJson('/api/activity?action=entry.created&resource_type=entry&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'entry.created')
            ->assertJsonPath('data.0.resource_type', 'entry');

        $this->actingAs($admin)->getJson('/api/activity?per_page=100')->assertUnprocessable();
    }

    public function test_activity_detail_never_exposes_non_allowlisted_metadata(): void
    {
        $admin = $this->role('Admin');
        $event = app(AuditLogger::class)->record($admin, 'document.created', 'document', 3, null, [
            'type' => 'pdf',
            'is_sensitive' => true,
            'checksum' => 'secret-checksum',
            'stored_name' => 'private-file.pdf',
        ]);

        $this->actingAs($admin)->getJson("/api/activity/{$event->id}")
            ->assertOk()
            ->assertJsonMissing(['checksum' => 'secret-checksum'])
            ->assertJsonMissing(['stored_name' => 'private-file.pdf'])
            ->assertJsonPath('metadata.type', 'pdf')
            ->assertJsonPath('metadata.is_sensitive', true);
    }
}
