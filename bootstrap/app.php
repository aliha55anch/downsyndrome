<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\EnsureApprovedDoctor;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['webhooks/safepay']);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'doctor.approved' => EnsureApprovedDoctor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Configure exception reporting here.
    })
    ->create();
