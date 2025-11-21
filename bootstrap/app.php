<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.both' => \App\Http\Middleware\AuthenticateWithBothGuards::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'employee' => \App\Http\Middleware\EnsureUserIsEmployee::class,
            'session.warmup' => \App\Http\Middleware\WarmupSession::class,
            'prevent.cache' => \App\Http\Middleware\EnsureSessionCookieHeaders::class,
            'log.employee.activity' => \App\Http\Middleware\LogEmployeeActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
