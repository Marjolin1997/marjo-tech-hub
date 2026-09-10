<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['access_control.owner_email' => null]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_access_control_seeder_creates_expected_roles_and_permissions_idempotently(): void
    {
        $this->seed(AccessControlSeeder::class); $this->seed(AccessControlSeeder::class);
        $this->assertSame(26, Permission::count()); $this->assertSame(4, Role::count());
        $this->assertSame(26, Role::findByName('Owner', 'web')->permissions()->count());
        $this->assertSame(26, Role::findByName('Admin', 'web')->permissions()->count());
        $this->assertSame(19, Role::findByName('Editor', 'web')->permissions()->count());
        $this->assertSame(7, Role::findByName('Viewer', 'web')->permissions()->count());
        $this->assertTrue(Role::findByName('Admin', 'web')->hasPermissionTo('messages.manage'));
        $this->assertTrue(Role::findByName('Admin', 'web')->hasPermissionTo('activity.view'));
        $this->assertFalse(Role::findByName('Editor', 'web')->hasPermissionTo('messages.manage'));
        $this->assertFalse(Role::findByName('Editor', 'web')->hasPermissionTo('activity.view'));
    }

    public function test_owner_bootstrap_is_explicit_and_does_not_promote_an_arbitrary_user(): void
    {
        $user = User::factory()->create(); $this->seed(AccessControlSeeder::class);
        $this->assertFalse($user->fresh()->hasAnyRole(['Owner', 'Admin', 'Editor', 'Viewer']));
    }

    public function test_configured_owner_is_promoted_without_hard_coding_an_account(): void
    {
        $this->seed(AccessControlSeeder::class);
        $owner = User::factory()->create(); $owner->assignRole('Viewer'); $other = User::factory()->create();
        config(['access_control.owner_email' => strtoupper($owner->email)]); $this->seed(AccessControlSeeder::class);
        $this->assertTrue($owner->fresh()->hasRole('Owner')); $this->assertTrue($owner->fresh()->hasRole('Viewer'));
        $this->assertFalse($other->fresh()->hasRole('Owner'));
    }

    public function test_configured_owner_must_resolve_to_an_existing_account(): void
    {
        config(['access_control.owner_email' => 'missing-owner@example.test']);
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('Configured RBAC owner account was not found.');
        $this->seed(AccessControlSeeder::class);
    }

    public function test_authenticated_identity_contains_effective_roles_and_permissions(): void
    {
        $this->seed(AccessControlSeeder::class); $user = User::factory()->create(); $user->assignRole('Viewer');
        $this->actingAs($user)->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.roles.0', 'Viewer')
            ->assertJsonPath('user.permissions', ['categories.view','documents.download','documents.view','entries.view','messages.send','messages.view','tags.view']);
    }
}
