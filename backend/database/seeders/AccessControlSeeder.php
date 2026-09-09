<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            $guard = 'web';
            $permissionNames = config('access_control.permissions', []);

            foreach ($permissionNames as $permissionName) {
                Permission::findOrCreate($permissionName, $guard);
            }

            foreach (config('access_control.roles', []) as $roleName) {
                $role = Role::findOrCreate($roleName, $guard);
                $configuredPermissions = config("access_control.role_permissions.{$roleName}", []);
                $role->syncPermissions(
                    $configuredPermissions === '*'
                        ? Permission::query()->where('guard_name', $guard)->get()
                        : $configuredPermissions
                );
            }

            $ownerEmail = trim((string) config('access_control.owner_email'));
            if ($ownerEmail !== '') {
                User::query()->where('email', $ownerEmail)->first()?->syncRoles(['Owner']);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
