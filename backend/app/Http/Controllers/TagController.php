<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'tags.view');
        $tags = $request->user()->tags()->withCount('entries')->orderBy('name')->get();
        return response()->json(['data' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'tags.create');
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]); $name = trim($data['name']); $slug = Str::slug($name);
        abort_if($slug === '', 422, 'Tag name must contain searchable characters.');
        $tag = $request->user()->tags()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        return response()->json(['data' => $tag], $tag->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        $this->requirePermission($request, 'tags.delete');
        abort_unless($tag->user_id === $request->user()->id, 404); $tag->delete();
        return response()->json([], 204);
    }

    private function requirePermission(Request $request, string $permission): void { abort_unless($request->user()->can($permission), 403, 'You do not have permission to perform this action.'); }
}
