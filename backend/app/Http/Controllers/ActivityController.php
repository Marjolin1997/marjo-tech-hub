<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeView($request);
        $data = $request->validate([
            'actor_id' => ['nullable', 'integer'], 'target_user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:80'], 'resource_type' => ['nullable', 'string', 'max:60'],
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = AuditEvent::query()->with(['actor:id,name', 'targetUser:id,name']);
        foreach (['actor_id', 'target_user_id', 'action', 'resource_type'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') $query->where($key, $data[$key]);
        }
        if (! empty($data['date_from'])) $query->where('created_at', '>=', $data['date_from'].' 00:00:00');
        if (! empty($data['date_to'])) $query->where('created_at', '<=', $data['date_to'].' 23:59:59');

        return response()->json($query->orderByDesc('created_at')->orderByDesc('id')->paginate($data['per_page'] ?? 20));
    }

    public function show(Request $request, AuditEvent $auditEvent): JsonResponse
    {
        $this->authorizeView($request);
        return response()->json($auditEvent->load(['actor:id,name', 'targetUser:id,name']));
    }

    public function filterOptions(Request $request): JsonResponse
    {
        $this->authorizeView($request);
        $actorIds = AuditEvent::query()->whereNotNull('actor_id')->distinct()->pluck('actor_id');
        $targetIds = AuditEvent::query()->whereNotNull('target_user_id')->distinct()->pluck('target_user_id');
        $users = User::query()->whereIn('id', $actorIds->merge($targetIds)->unique())->orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => [
            'users' => $users,
            'actions' => AuditEvent::query()->distinct()->orderBy('action')->pluck('action'),
            'resource_types' => AuditEvent::query()->distinct()->orderBy('resource_type')->pluck('resource_type'),
        ]]);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()->can('activity.view'), 403, 'You do not have permission to view activity.');
    }
}
