<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityAuditApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_requires_explicit_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/activity')->assertForbidden();
    }

    public function test_authorized_user_can_filter_paginated_activity(): void
    {
        $viewer = User::factory()->create();
        Permission::findOrCreate('activity.view', 'web');
        $viewer->givePermissionTo('activity.view');
        $actor = User::factory()->create();
        $logger = app(AuditLogger::class);
        $logger->record($actor, 'entry.created', 'entry', 11, 'Safe title', ['type' => 'note']);
        $logger->record($actor, 'access.roles_updated', 'user', $viewer->id, $viewer->name, ['added_roles' => ['Viewer']], $viewer);

        $this->actingAs($viewer)->getJson('/api/activity?action=entry.created&per_page=10')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.action', 'entry.created')->assertJsonPath('data.0.resource_label', 'Safe title');
    }

    public function test_logger_discards_unapproved_sensitive_metadata(): void
    {
        $actor = User::factory()->create();
        $event = app(AuditLogger::class)->record($actor, 'entry.updated', 'entry', 5, 'Visible label', [
            'type' => 'command', 'content' => 'SECRET COMMAND', 'password' => 'secret', 'token' => 'token', 'path' => '/private/file',
        ]);

        $event->refresh();
        $this->assertSame(['type' => 'command'], $event->metadata);
        $encoded = json_encode($event->toArray());
        $this->assertStringNotContainsString('SECRET COMMAND', $encoded);
        $this->assertStringNotContainsString('/private/file', $encoded);
    }

    public function test_activity_detail_is_permission_protected(): void
    {
        $event = AuditEvent::query()->create(['action' => 'system.test', 'resource_type' => 'system']);
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/activity/'.$event->id)->assertForbidden();
    }
}
