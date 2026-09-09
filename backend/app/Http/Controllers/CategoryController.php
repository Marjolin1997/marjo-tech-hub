<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $this->requirePermission($request, 'categories.view');
        $categories = $request->user()->categories()->whereNull('parent_id')->with(['children.children.children'])->withCount('entries')->orderBy('sort_order')->orderBy('name')->get();
        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $this->requirePermission($request, 'categories.create');
        $data = $request->validated(); $this->assertOwnedParent($request, $data['parent_id'] ?? null);
        $data['slug'] = $this->uniqueSlug($request, $data['name'], $data['parent_id'] ?? null);
        $category = $request->user()->categories()->create($data);
        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $this->requirePermission($request, 'categories.update'); $this->assertOwner($request, $category);
        $data = $request->validated(); $parentId = $data['parent_id'] ?? null; $this->assertOwnedParent($request, $parentId);
        abort_if($parentId === $category->id, 422, 'A category cannot be its own parent.');
        abort_if($parentId && $this->isDescendant($category, $parentId), 422, 'A category cannot be moved inside one of its descendants.');
        $data['slug'] = $this->uniqueSlug($request, $data['name'], $parentId, $category->id); $category->update($data);
        return new CategoryResource($category->fresh());
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->requirePermission($request, 'categories.delete'); $this->assertOwner($request, $category); $category->delete();
        return response()->json([], 204);
    }

    private function requirePermission(Request $request, string $permission): void { abort_unless($request->user()->can($permission), 403, 'You do not have permission to perform this action.'); }
    private function assertOwner(Request $request, Category $category): void { abort_unless($category->user_id === $request->user()->id, 404); }
    private function assertOwnedParent(Request $request, ?int $parentId): void { if ($parentId === null) return; abort_unless($request->user()->categories()->whereKey($parentId)->exists(), 422, 'The selected parent category is invalid.'); }
    private function isDescendant(Category $category, int $candidateId): bool { $cursor = Category::find($candidateId); while ($cursor) { if ($cursor->parent_id === $category->id) return true; $cursor = $cursor->parent; } return false; }
    private function uniqueSlug(Request $request, string $name, ?int $parentId, ?int $ignoreId = null): string { $base = Str::slug($name) ?: 'category'; $slug = $base; $suffix = 2; while ($request->user()->categories()->where('parent_id', $parentId)->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) $slug = $base.'-'.$suffix++; return $slug; }
}
