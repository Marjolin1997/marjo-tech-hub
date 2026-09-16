<?php

namespace App\Http\Controllers;

use App\Models\NetworkTestResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NetworkTestResultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $results = NetworkTestResult::query()
            ->where('user_id', $request->user()->id)
            ->latest('tested_at')
            ->paginate(20);

        return response()->json($results);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $data['tested_at'] ??= now();
        $result = $request->user()->networkTestResults()->create($data);

        return response()->json(['result' => $result], 201);
    }

    public function show(Request $request, NetworkTestResult $networkTestResult): JsonResponse
    {
        $this->assertOwner($request, $networkTestResult);
        return response()->json(['result' => $networkTestResult]);
    }

    public function update(Request $request, NetworkTestResult $networkTestResult): JsonResponse
    {
        $this->assertOwner($request, $networkTestResult);
        $networkTestResult->update($request->validate($this->rules()));
        return response()->json(['result' => $networkTestResult->fresh()]);
    }

    public function destroy(Request $request, NetworkTestResult $networkTestResult): JsonResponse
    {
        $this->assertOwner($request, $networkTestResult);
        $networkTestResult->delete();
        return response()->json(['message' => 'Diagnostic result deleted.']);
    }

    private function rules(): array
    {
        return [
            'latency_ms' => ['required', 'numeric', 'min:0', 'max:60000'],
            'jitter_ms' => ['required', 'numeric', 'min:0', 'max:60000'],
            'download_mbps' => ['required', 'numeric', 'min:0', 'max:100000'],
            'upload_mbps' => ['required', 'numeric', 'min:0', 'max:100000'],
            'connection_quality' => ['required', Rule::in(['Excellent', 'Good', 'Fair', 'Limited'])],
            'effective_type' => ['nullable', Rule::in(['slow-2g', '2g', '3g', '4g'])],
            'reported_downlink_mbps' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'reported_rtt_ms' => ['nullable', 'integer', 'min:0', 'max:60000'],
            'user_agent_family' => ['nullable', 'string', 'max:80'],
            'tested_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    private function assertOwner(Request $request, NetworkTestResult $result): void
    {
        abort_unless($result->user_id === $request->user()->id, 404);
    }
}
