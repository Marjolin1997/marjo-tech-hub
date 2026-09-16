<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class NetworkSpeedTestController extends Controller
{
    private const DEFAULT_DOWNLOAD_BYTES = 10_000_000;
    private const MAX_DOWNLOAD_BYTES = 25_000_000;
    private const MAX_UPLOAD_BYTES = 10_000_000;

    public function ping(): Response
    {
        return response('ok', 200, $this->noCacheHeaders('text/plain; charset=UTF-8'));
    }

    public function download(Request $request): Response
    {
        $bytes = (int) $request->integer('bytes', self::DEFAULT_DOWNLOAD_BYTES);
        $bytes = max(1_000_000, min($bytes, self::MAX_DOWNLOAD_BYTES));

        // Incompressible random bytes prevent HTTP compression from inflating the result.
        $payload = random_bytes($bytes);

        return response($payload, 200, $this->noCacheHeaders('application/octet-stream') + [
            'Content-Length' => (string) $bytes,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function upload(Request $request): Response|JsonResponse
    {
        $contentLength = (int) $request->header('Content-Length', 0);
        if ($contentLength > self::MAX_UPLOAD_BYTES) {
            return response('Payload too large', 413, $this->noCacheHeaders('text/plain; charset=UTF-8'));
        }

        $payload = $request->getContent();
        $bytes = strlen($payload);
        if ($bytes > self::MAX_UPLOAD_BYTES) {
            return response('Payload too large', 413, $this->noCacheHeaders('text/plain; charset=UTF-8'));
        }

        Log::debug('Network speed test upload completed', ['bytes' => $bytes]);

        return response()->json(['received_bytes' => $bytes], 200, $this->noCacheHeaders('application/json'));
    }

    private function noCacheHeaders(string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
