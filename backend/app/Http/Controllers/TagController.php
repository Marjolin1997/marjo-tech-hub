<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tags = $request->user()->tags()->withCount('entries')->orderBy('name')->get();
        return response()->json(['data' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);
        $name = trim($data['name']);
        $slug = Str::slug($name);

        abort_if($slug === '', 422, 'Tag name must contain searchable characters.');

        $tag = $request->user()->tags()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        return response()->json(['data' => $tag], $tag->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        abort_unless($tag->user_id === $request->user()->id, 404);
        $tag->delete();
        return response()->json([], 204);
    }
}
