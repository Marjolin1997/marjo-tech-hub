<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccessControl\RoleAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('users.view'), 403);
        $users = User::query()->with('roles:id,name')->orderBy('name')->paginate(25);
        return response()->json($users);
    }

    public function roles(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('roles.view'), 403);
        $roles = Role::query()->where('guard_name', 'web')->with('permissions:id,name')->orderBy('name')->get()
            ->map(fn (Role $role) => ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->sort()->values()]);
        return response()->json(['data' => $roles]);
    }

    public function permissions(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('permissions.view'), 403);
        return response()->json(['data' => Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')]);
    }

    public function updateUserRoles(Request $request, User $user, RoleAssignmentService $service): JsonResponse
    {
        abort_unless($request->user()->can('roles.manage') && $request->user()->can('users.update'), 403);
        $validated = $request->validate(['roles' => ['required', 'array', 'min:1'], 'roles.*' => ['required', 'string', 'distinct']]);
        $updated = $service->sync($request->user(), $user, $validated['roles']);
        return response()->json(['data' => ['id' => $updated->id, 'roles' => $updated->getRoleNames()->sort()->values()]]);
    }
}
