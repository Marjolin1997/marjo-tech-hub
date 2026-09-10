<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntryRequest;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $this->requirePermission($request, 'entries.view');
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'], 'type' => ['nullable', 'in:command,snippet,note'],
            'tag_id' => ['nullable', 'integer'], 'favorite' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:120'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $user = $request->user();
        $query = $user->entries()->with(['category', 'tags'])->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $user->id)]);
        if (!empty($validated['category_id'])) $query->where('category_id', $validated['category_id']);
        if (!empty($validated['type'])) $query->where('type', $validated['type']);
        if (!empty($validated['tag_id'])) $query->whereHas('tags', fn ($q) => $q->where('tags.id', $validated['tag_id'])->where('tags.user_id', $user->id));
        if (($validated['favorite'] ?? false) === true) $query->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id));
        if (!empty($validated['q'])) { $term = '%'.addcslashes($validated['q'], '%_\\').'%'; $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhere('content', 'like', $term)); }
        return EntryResource::collection($query->orderBy('sort_order')->latest('updated_at')->paginate($validated['per_page'] ?? 20));
    }

    public function store(EntryRequest $request): JsonResponse
    {
        $this->requirePermission($request, 'entries.create');
        $data = $request->validated(); $tagIds = $data['tag_ids'] ?? [];
        $this->assertOwnedCategory($request, $data['category_id'] ?? null); $this->assertOwnedTags($request, $tagIds);
        $payload = Arr::except($data, ['tag_ids']); $payload['slug'] = $this->uniqueSlug($request, $data['title']);
        $entry = $request->user()->entries()->create($payload); $entry->tags()->sync($tagIds);
        return (new EntryResource($this->hydrate($request, $entry)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Entry $entry): EntryResource
    {
        $this->requirePermission($request, 'entries.view'); $this->assertOwner($request, $entry);
        return new EntryResource($this->hydrate($request, $entry));
    }

    public function update(EntryRequest $request, Entry $entry): EntryResource
    {
        $this->requirePermission($request, 'entries.update'); $this->assertOwner($request, $entry);
        $data = $request->validated(); $tagIds = $data['tag_ids'] ?? [];
        $this->assertOwnedCategory($request, $data['category_id'] ?? null); $this->assertOwnedTags($request, $tagIds);
        $payload = Arr::except($data, ['tag_ids']); $payload['slug'] = $this->uniqueSlug($request, $data['title'], $entry->id);
        $entry->update($payload); $entry->tags()->sync($tagIds);
        return new EntryResource($this->hydrate($request, $entry->fresh()));
    }

    public function destroy(Request $request, Entry $entry): JsonResponse
    {
        $this->requirePermission($request, 'entries.delete'); $this->assertOwner($request, $entry); $entry->delete();
        return response()->json([], 204);
    }

    private function requirePermission(Request $request, string $permission): void { abort_unless($request->user()->can($permission), 403, 'You do not have permission to perform this action.'); }
    private function hydrate(Request $request, Entry $entry): Entry { $entry->load(['category', 'tags']); $entry->loadExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $request->user()->id)]); return $entry; }
    private function assertOwner(Request $request, Entry $entry): void { abort_unless($entry->user_id === $request->user()->id, 404); }
    private function assertOwnedCategory(Request $request, ?int $id): void { if ($id !== null) abort_unless($request->user()->categories()->whereKey($id)->exists(), 422, 'The selected category is invalid.'); }
    private function assertOwnedTags(Request $request, array $ids): void { if ($ids) abort_unless($request->user()->tags()->whereKey($ids)->count() === count(array_unique($ids)), 422, 'One or more selected tags are invalid.'); }
    private function uniqueSlug(Request $request, string $title, ?int $ignoreId = null): string { $base = Str::slug($title) ?: 'entry'; $slug = $base; $suffix = 2; while ($request->user()->entries()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) $slug = $base.'-'.$suffix++; return $slug; }
}
