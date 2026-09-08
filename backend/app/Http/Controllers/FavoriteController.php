<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request, Entry $entry): JsonResponse
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
        $request->user()->favorites()->firstOrCreate(['entry_id' => $entry->id]);
        return response()->json(['is_favorite' => true]);
    }

    public function destroy(Request $request, Entry $entry): JsonResponse
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
        $request->user()->favorites()->where('entry_id', $entry->id)->delete();
        return response()->json(['is_favorite' => false]);
    }
}
