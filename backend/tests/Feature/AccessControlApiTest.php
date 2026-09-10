<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessControlApiTest extends TestCase
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

    public function test_viewer_and_editor_cannot_access_administration_api(): void
    {
        foreach (['Viewer', 'Editor'] as $role) {
            $user = $this->role($role);
            $this->actingAs($user)->getJson('/api/access-control/users')->assertForbidden();
            $this->actingAs($user)->getJson('/api/access-control/roles')->assertForbidden();
            $this->actingAs($user)->getJson('/api/access-control/permissions')->assertForbidden();
        }
    }

    public function test_admin_can_read_catalogues_and_assign_non_owner_roles(): void
    {
        $admin = $this->role('Admin');
        $target = $this->role('Viewer');
        $this->actingAs($admin)->getJson('/api/access-control/users')->assertOk();
        $this->actingAs($admin)->getJson('/api/access-control/roles')->assertOk()->assertJsonCount(4, 'data');
        $this->actingAs($admin)->getJson('/api/access-control/permissions')->assertOk()->assertJsonCount(22, 'data');
        $this->actingAs($admin)->putJson("/api/access-control/users/{$target->id}/roles", ['roles' => ['Editor']])->assertOk();
        $this->assertTrue($target->fresh()->hasRole('Editor'));
    }

    public function test_admin_cannot_grant_or_revoke_owner(): void
    {
        $admin = $this->role('Admin');
        $target = $this->role('Viewer');
        $owner = $this->role('Owner');
        $this->actingAs($admin)->putJson("/api/access-control/users/{$target->id}/roles", ['roles' => ['Owner']])->assertForbidden();
        $this->actingAs($admin)->putJson("/api/access-control/users/{$owner->id}/roles", ['roles' => ['Admin']])->assertForbidden();
        $this->assertTrue($owner->fresh()->hasRole('Owner'));
    }

    public function test_final_owner_cannot_remove_own_owner_role(): void
    {
        $owner = $this->role('Owner');
        $this->actingAs($owner)->putJson("/api/access-control/users/{$owner->id}/roles", ['roles' => ['Admin']])->assertUnprocessable();
        $this->assertTrue($owner->fresh()->hasRole('Owner'));
    }

    public function test_owner_can_transfer_owner_access_when_another_owner_remains(): void
    {
        $ownerA = $this->role('Owner');
        $ownerB = $this->role('Owner');
        $this->actingAs($ownerA)->putJson("/api/access-control/users/{$ownerA->id}/roles", ['roles' => ['Admin']])->assertOk();
        $this->assertFalse($ownerA->fresh()->hasRole('Owner'));
        $this->assertTrue($ownerB->fresh()->hasRole('Owner'));
    }

    public function test_unknown_roles_are_rejected(): void
    {
        $owner = $this->role('Owner');
        $target = $this->role('Viewer');
        $this->actingAs($owner)->putJson("/api/access-control/users/{$target->id}/roles", ['roles' => ['SuperAdmin']])->assertUnprocessable();
        $this->assertTrue($target->fresh()->hasRole('Viewer'));
    }
}
