<?php

namespace App\Services\AccessControl;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleAssignmentService
{
    public function sync(User $actor, User $target, array $roleNames): User
    {
        $allowed = config('access_control.roles', []);
        $roleNames = array_values(array_unique($roleNames));
        foreach ($roleNames as $role) {
            if (! in_array($role, $allowed, true)) {
                throw ValidationException::withMessages(['roles' => ['One or more selected roles are invalid.']]);
            }
        }

        $targetIsOwner = $target->hasRole('Owner');
        $willBeOwner = in_array('Owner', $roleNames, true);

        if (($targetIsOwner || $willBeOwner) && ! $actor->hasRole('Owner')) {
            throw new AuthorizationException('Only an Owner can manage the Owner role.');
        }

        if ($targetIsOwner && ! $willBeOwner && $this->ownerCount() <= 1) {
            throw ValidationException::withMessages(['roles' => ['The final Owner cannot lose Owner access.']]);
        }

        return DB::transaction(function () use ($target, $roleNames): User {
            $target->syncRoles($roleNames);
            return $target->fresh();
        });
    }

    private function ownerCount(): int
    {
        return User::role('Owner')->count();
    }
}
