<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    private const TYPES = ['service', 'website', 'api', 'worker', 'library', 'database', 'infrastructure'];
    private const LIFECYCLES = ['development', 'production', 'maintenance', 'deprecated'];

    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'services.view');
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'], 'type' => ['nullable', Rule::in(self::TYPES)],
            'lifecycle' => ['nullable', Rule::in(self::LIFECYCLES)], 'technology' => ['nullable', 'string', 'max:60'],
            'per_page' => ['nullable', 'integer', 'min:6', 'max:48'],
        ]);
        $query = $request->user()->services()->latest('updated_at');
        if ($q = trim((string) ($validated['q'] ?? ''))) {
            $term = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $term)->orWhere('description', 'like', $term)->orWhere('owner_label', 'like', $term));
        }
        if (!empty($validated['type'])) $query->where('type', $validated['type']);
        if (!empty($validated['lifecycle'])) $query->where('lifecycle', $validated['lifecycle']);
        if (!empty($validated['technology'])) $query->whereJsonContains('technologies', $validated['technology']);
        return response()->json($query->paginate($validated['per_page'] ?? 12));
    }

    public function store(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'services.create');
        $data = $this->validatedPayload($request); $data['slug'] = $this->uniqueSlug($request, $data['name']);
        return response()->json(['data' => $request->user()->services()->create($data)], 201);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        $this->requirePermission($request, 'services.view');
        return response()->json(['data' => $this->owned($request, $service)]);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $this->requirePermission($request, 'services.update'); $service = $this->owned($request, $service);
        $data = $this->validatedPayload($request);
        if ($data['name'] !== $service->name) $data['slug'] = $this->uniqueSlug($request, $data['name'], $service->id);
        $service->update($data); return response()->json(['data' => $service->fresh()]);
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->requirePermission($request, 'services.delete'); $this->owned($request, $service)->delete();
        return response()->json(null, 204);
    }

    private function validatedPayload(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'type' => ['required', Rule::in(self::TYPES)],
            'lifecycle' => ['required', Rule::in(self::LIFECYCLES)], 'description' => ['nullable', 'string', 'max:2000'],
            'repository_url' => ['nullable', 'url:http,https', 'max:2048'], 'production_url' => ['nullable', 'url:http,https', 'max:2048'],
            'api_base_url' => ['nullable', 'url:http,https', 'max:2048'], 'technologies' => ['nullable', 'array', 'max:30'],
            'technologies.*' => ['string', 'max:60', 'distinct'], 'owner_label' => ['nullable', 'string', 'max:120'],
        ]);
        $data['name'] = trim($data['name']); $data['description'] = isset($data['description']) ? trim($data['description']) : null;
        $data['owner_label'] = isset($data['owner_label']) ? trim($data['owner_label']) : null;
        $data['technologies'] = collect($data['technologies'] ?? [])->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
        return $data;
    }

    private function requirePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403, 'You do not have permission to perform this action.');
    }
    private function owned(Request $request, Service $service): Service { abort_unless($service->user_id === $request->user()->id, 404); return $service; }
    private function uniqueSlug(Request $request, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'service'; $slug = $base; $suffix = 2;
        while ($request->user()->services()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) $slug = $base.'-'.$suffix++;
        return $slug;
    }
}
