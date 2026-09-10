<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('activity.view'), 403, 'You do not have permission to view activity.');

        $data = $request->validate([
            'actor_id' => ['nullable', 'integer'],
            'target_user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:80'],
            'resource_type' => ['nullable', 'string', 'max:60'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = AuditEvent::query()->with(['actor:id,name,email', 'targetUser:id,name,email']);
        foreach (['actor_id', 'target_user_id', 'action', 'resource_type'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') $query->where($key, $data[$key]);
        }
        if (! empty($data['date_from'])) $query->where('created_at', '>=', $data['date_from'].' 00:00:00');
        if (! empty($data['date_to'])) $query->where('created_at', '<=', $data['date_to'].' 23:59:59');

        return response()->json($query->orderByDesc('created_at')->orderByDesc('id')->paginate($data['per_page'] ?? 20));
    }

    public function show(Request $request, AuditEvent $auditEvent)
    {
        abort_unless($request->user()->can('activity.view'), 403, 'You do not have permission to view activity.');
        return response()->json($auditEvent->load(['actor:id,name,email', 'targetUser:id,name,email']));
    }
}
