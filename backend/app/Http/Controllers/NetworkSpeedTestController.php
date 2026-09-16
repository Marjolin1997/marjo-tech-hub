<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
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

    public function networkInfo(Request $request): JsonResponse
    {
        $requestIp = (string) $request->ip();
        $isLocal = $this->isPrivateOrReservedIp($requestIp);

        try {
            // In local Docker development the inbound address is private/loopback, so
            // resolving the backend's outbound public address represents the same NAT
            // connection used by the browser. In production we resolve the client IP.
            $url = $isLocal ? 'https://ipwho.is/' : 'https://ipwho.is/'.rawurlencode($requestIp);
            $lookup = Http::acceptJson()->timeout(3)->retry(1, 100)->get($url);
            $data = $lookup->successful() ? $lookup->json() : null;

            if (! is_array($data) || ($data['success'] ?? true) === false) {
                throw new \RuntimeException('IP intelligence provider returned an invalid response.');
            }

            return response()->json([
                'ip' => $data['ip'] ?? ($isLocal ? null : $requestIp),
                'isp' => data_get($data, 'connection.isp'),
                'organization' => data_get($data, 'connection.org'),
                'asn' => data_get($data, 'connection.asn'),
                'country' => $data['country'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'city' => $data['city'] ?? null,
                'local_environment' => $isLocal,
                'source' => 'IPWhois',
            ], 200, $this->noCacheHeaders('application/json'));
        } catch (\Throwable $exception) {
            Log::warning('Network diagnostics IP lookup unavailable', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'ip' => $isLocal ? null : $requestIp,
                'isp' => null,
                'organization' => null,
                'asn' => null,
                'country' => null,
                'country_code' => null,
                'city' => null,
                'local_environment' => $isLocal,
                'source' => null,
            ], 200, $this->noCacheHeaders('application/json'));
        }
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

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
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
