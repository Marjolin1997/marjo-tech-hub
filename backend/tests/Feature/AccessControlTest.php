<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_access_control_seeder_creates_expected_roles_and_permissions_idempotently(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->seed(AccessControlSeeder::class);

        $this->assertSame(22, Permission::count());
        $this->assertSame(4, Role::count());
        $this->assertSame(22, Role::findByName('Owner', 'web')->permissions()->count());
        $this->assertSame(21, Role::findByName('Admin', 'web')->permissions()->count());
        $this->assertSame(17, Role::findByName('Editor', 'web')->permissions()->count());
        $this->assertSame(5, Role::findByName('Viewer', 'web')->permissions()->count());
    }

    public function test_owner_bootstrap_is_explicit_and_does_not_promote_an_arbitrary_user(): void
    {
        $user = User::factory()->create();
        config(['access_control.owner_email' => null]);

        $this->seed(AccessControlSeeder::class);

        $this->assertFalse($user->fresh()->hasAnyRole(['Owner', 'Admin', 'Editor', 'Viewer']));
    }

    public function test_configured_owner_is_promoted_without_hard_coding_an_account(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        config(['access_control.owner_email' => $owner->email]);

        $this->seed(AccessControlSeeder::class);

        $this->assertTrue($owner->fresh()->hasRole('Owner'));
        $this->assertFalse($other->fresh()->hasRole('Owner'));
    }

    public function test_authenticated_identity_contains_effective_roles_and_permissions(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.roles.0', 'Viewer')
            ->assertJsonCount(5, 'user.permissions')
            ->assertJsonFragment(['documents.download']);
    }
}
