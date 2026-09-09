<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
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
            foreach (config('access_control.permissions', []) as $permissionName) {
                Permission::findOrCreate($permissionName, $guard);
            }

            foreach (config('access_control.roles', []) as $roleName) {
                $role = Role::findOrCreate($roleName, $guard);
                $configured = config("access_control.role_permissions.{$roleName}", []);
                $role->syncPermissions($configured === '*' ? Permission::query()->where('guard_name', $guard)->get() : $configured);
            }

            $ownerEmail = trim((string) config('access_control.owner_email'));
            if ($ownerEmail !== '') {
                $owner = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($ownerEmail)])->first();
                if (! $owner) {
                    throw new RuntimeException('Configured RBAC owner account was not found.');
                }
                $owner->assignRole('Owner');
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
