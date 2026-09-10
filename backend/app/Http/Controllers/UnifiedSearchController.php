<?php

namespace App\Http\Controllers;

use App\Models\SearchHistory;
use App\Services\Search\UnifiedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnifiedSearchController extends Controller
{
    public function index(Request $request, UnifiedSearchService $search): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:120']]);
        $query = trim($validated['q']);
        if (mb_strlen($query) < 2) {
            return response()->json(['message' => 'The search query must be at least 2 characters.', 'errors' => ['q' => ['The search query must be at least 2 characters.']]], 422);
        }

        $this->remember($request, $query);

        return response()->json($search->search($request->user(), $query));
    }

    public function history(Request $request): JsonResponse
    {
        $rows = SearchHistory::query()->where('user_id', $request->user()->id)
            ->orderByDesc('last_used_at')->limit(10)->get(['id', 'query', 'last_used_at']);

        return response()->json(['data' => $rows]);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        SearchHistory::query()->where('user_id', $request->user()->id)->delete();
        return response()->json(['cleared' => true]);
    }

    private function remember(Request $request, string $query): void
    {
        $normalized = Str::lower(preg_replace('/\s+/u', ' ', trim($query)) ?? trim($query));
        SearchHistory::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'normalized_query' => $normalized],
            ['query' => $query, 'last_used_at' => now()]
        );

        $keep = SearchHistory::query()->where('user_id', $request->user()->id)
            ->orderByDesc('last_used_at')->limit(10)->pluck('id');
        SearchHistory::query()->where('user_id', $request->user()->id)->whereNotIn('id', $keep)->delete();
    }
}
