<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->api(prepend: [StartSession::class]);

        // This endpoint accepts only a bounded anonymous byte probe for the
        // public Network Diagnostics test. It does not mutate application
        // state, use authentication, or persist the uploaded payload.
        $middleware->validateCsrfTokens(except: [
            'api/network-test/upload',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep framework JSON semantics; domain errors are handled at their boundary.
    })
    ->create();
