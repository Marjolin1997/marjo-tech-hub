<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityRiskController extends Controller
{
    private const MAX_RESPONSE_BYTES = 262144;

    public function inspect(Request $request): JsonResponse
    {
        $validated = $request->validate(['target' => ['required', 'string', 'max:253']]);

        try {
            [$url, $host, $ip] = $this->validatedTarget($validated['target']);
            $http = $this->fetchPinned($url, $host, $ip);
            $certificate = $this->certificate($host, $ip);
            $findings = $this->findings($http['headers'], $certificate, $http['status']);

            return response()->json([
                'target' => ['url' => $url, 'host' => $host, 'resolved_ip' => $ip],
                'http' => ['status' => $http['status'], 'content_type' => $http['content_type']],
                'tls' => $certificate,
                'summary' => collect($findings)->countBy('status'),
                'findings' => $findings,
                'scanned_at' => now()->toIso8601String(),
                'notice' => 'This is a focused configuration check, not proof that a site is secure.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'The public site could not be inspected safely. Check the target and try again.'], 502);
        }
    }

    private function validatedTarget(string $input): array
    {
        $input = trim($input);
        if (!Str::contains($input, '://')) {
            $input = 'https://' . $input;
        }

        $parts = parse_url($input);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new \InvalidArgumentException('Enter a valid public HTTP or HTTPS domain.');
        }
        if (($parts['port'] ?? null) && !in_array((int) $parts['port'], [80, 443], true)) {
            throw new \InvalidArgumentException('Only standard HTTP and HTTPS ports are supported.');
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new \InvalidArgumentException('Local and private targets are not allowed.');
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        $ips = array_values(array_unique(array_filter(array_map(fn ($r) => $r['ip'] ?? $r['ipv6'] ?? null, $records))));
        if ($ips === []) {
            throw new \InvalidArgumentException('The domain could not be resolved.');
        }
        foreach ($ips as $candidate) {
            if (!$this->isPublicIp($candidate)) {
                throw new \InvalidArgumentException('Local, private and reserved network targets are not allowed.');
            }
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        return ["{$scheme}://{$host}{$port}{$path}{$query}", $host, $ips[0]];
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function fetchPinned(string $url, string $host, string $ip): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('HTTP inspection transport is unavailable.');
        }

        $handle = curl_init($url);
        $headers = [];
        $bodyBytes = 0;
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'MarjoTechHub-SecurityCheck/1.0',
            CURLOPT_RESOLVE => ["{$host}:443:{$ip}", "{$host}:80:{$ip}"],
            CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$headers): int {
                $length = strlen($line);
                $line = trim($line);
                if ($line !== '' && str_contains($line, ':')) {
                    [$name, $value] = array_map('trim', explode(':', $line, 2));
                    $headers[strtolower($name)] = $value;
                }
                return $length;
            },
            CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use (&$bodyBytes): int {
                $bodyBytes += strlen($chunk);
                return $bodyBytes > self::MAX_RESPONSE_BYTES ? 0 : strlen($chunk);
            },
        ]);
        curl_exec($handle);
        $errno = curl_errno($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $contentType = curl_getinfo($handle, CURLINFO_CONTENT_TYPE) ?: null;
        curl_close($handle);
        if ($status === 0 || ($errno !== 0 && $bodyBytes <= self::MAX_RESPONSE_BYTES)) {
            throw new \RuntimeException('Remote request failed.');
        }

        return ['status' => $status, 'headers' => $headers, 'content_type' => $contentType];
    }

    private function certificate(string $host, string $ip): array
    {
        $context = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ]]);
        $socket = @stream_socket_client("ssl://[{$ip}]:443", $errno, $error, 5, STREAM_CLIENT_CONNECT, $context);
        if (!$socket && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $socket = @stream_socket_client("ssl://{$ip}:443", $errno, $error, 5, STREAM_CLIENT_CONNECT, $context);
        }
        if (!$socket) {
            return ['available' => false, 'valid' => false];
        }
        $params = stream_context_get_params($socket);
        fclose($socket);
        $parsed = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? null) ?: [];
        $validTo = isset($parsed['validTo_time_t']) ? (int) $parsed['validTo_time_t'] : null;
        $days = $validTo ? (int) floor(($validTo - time()) / 86400) : null;
        return [
            'available' => true,
            'valid' => $validTo ? $validTo > time() : false,
            'issuer' => $parsed['issuer']['O'] ?? $parsed['issuer']['CN'] ?? null,
            'subject' => $parsed['subject']['CN'] ?? null,
            'valid_until' => $validTo ? gmdate(DATE_ATOM, $validTo) : null,
            'days_remaining' => $days,
        ];
    }

    private function findings(array $headers, array $tls, int $status): array
    {
        $items = [];
        $items[] = $this->finding('tls', 'TLS certificate', ($tls['valid'] ?? false) ? (($tls['days_remaining'] ?? 0) < 30 ? 'warning' : 'passed') : 'failed', $tls['available'] ? (($tls['days_remaining'] ?? null) . ' days remaining') : 'No valid TLS certificate was observed.', 'TLS protects data in transit and authenticates the site.', 'Renew certificates before expiry and use a certificate valid for the requested hostname.');
        $checks = [
            ['strict-transport-security', 'HSTS', 'Forces compatible browsers to use HTTPS.', 'Add a Strict-Transport-Security header after confirming the site is HTTPS-only.'],
            ['content-security-policy', 'Content Security Policy', 'Restricts which resources a browser may execute or load.', 'Define a restrictive Content-Security-Policy and tighten it for the application.'],
            ['x-content-type-options', 'MIME sniffing protection', 'Prevents browsers from guessing executable content types.', 'Send X-Content-Type-Options: nosniff.'],
            ['referrer-policy', 'Referrer Policy', 'Limits URL information leaked through the Referer header.', 'Set an appropriate Referrer-Policy such as strict-origin-when-cross-origin.'],
            ['permissions-policy', 'Permissions Policy', 'Constrains access to browser capabilities.', 'Define a Permissions-Policy for features the application does not need.'],
        ];
        foreach ($checks as [$header, $title, $why, $fix]) {
            $items[] = $this->finding($header, $title, isset($headers[$header]) ? 'passed' : 'warning', $headers[$header] ?? 'Header not observed.', $why, $fix);
        }
        $frameProtected = isset($headers['content-security-policy']) && str_contains(strtolower($headers['content-security-policy']), 'frame-ancestors') || isset($headers['x-frame-options']);
        $items[] = $this->finding('framing', 'Framing protection', $frameProtected ? 'passed' : 'warning', $frameProtected ? 'Framing restriction observed.' : 'No frame-ancestors or X-Frame-Options observed.', 'Reduces clickjacking exposure.', 'Prefer CSP frame-ancestors; X-Frame-Options can provide legacy coverage.');
        $items[] = $this->finding('http-status', 'HTTP response', $status >= 200 && $status < 400 ? 'passed' : 'info', "HTTP {$status}", 'Confirms how the inspected URL responds without following redirects.', 'Review unexpected error or redirect responses.');
        return $items;
    }

    private function finding(string $key, string $title, string $status, string $evidence, string $why, string $recommendation): array
    {
        return compact('key', 'title', 'status', 'evidence', 'why', 'recommendation');
    }
}
