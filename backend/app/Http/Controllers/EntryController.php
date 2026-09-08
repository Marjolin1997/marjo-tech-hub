<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntryRequest;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:command,snippet,note'],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = $request->user()->entries()->with('category');

        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }
        if (!empty($validated['q'])) {
            $term = '%'.addcslashes($validated['q'], '%_\\').'%';
            $query->where(fn ($q) => $q
                ->where('title', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhere('content', 'like', $term));
        }

        return EntryResource::collection(
            $query->orderBy('sort_order')->latest('updated_at')->paginate($validated['per_page'] ?? 20)
        );
    }

    public function store(EntryRequest $request): EntryResource
    {
        $data = $request->validated();
        $this->assertOwnedCategory($request, $data['category_id'] ?? null);
        $data['slug'] = $this->uniqueSlug($request, $data['title']);

        $entry = $request->user()->entries()->create($data);

        return new EntryResource($entry->load('category'));
    }

    public function show(Request $request, Entry $entry): EntryResource
    {
        $this->assertOwner($request, $entry);
        return new EntryResource($entry->load('category'));
    }

    public function update(EntryRequest $request, Entry $entry): EntryResource
    {
        $this->assertOwner($request, $entry);
        $data = $request->validated();
        $this->assertOwnedCategory($request, $data['category_id'] ?? null);
        $data['slug'] = $this->uniqueSlug($request, $data['title'], $entry->id);
        $entry->update($data);

        return new EntryResource($entry->fresh()->load('category'));
    }

    public function destroy(Request $request, Entry $entry): JsonResponse
    {
        $this->assertOwner($request, $entry);
        $entry->delete();

        return response()->json([], 204);
    }

    private function assertOwner(Request $request, Entry $entry): void
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
    }

    private function assertOwnedCategory(Request $request, ?int $categoryId): void
    {
        if ($categoryId === null) return;
        abort_unless($request->user()->categories()->whereKey($categoryId)->exists(), 422, 'The selected category is invalid.');
    }

    private function uniqueSlug(Request $request, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'entry';
        $slug = $base;
        $suffix = 2;

        while ($request->user()->entries()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
