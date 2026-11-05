<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'            => \App\Http\Middleware\AdminMiddleware::class,
            'admin.permission' => \App\Http\Middleware\EnsureAdminPermission::class,
            'superadmin'       => \App\Http\Middleware\EnsureSuperAdmin::class,
            'ownership.role'   => \App\Http\Middleware\EnsureOwnershipRole::class,
            'not.impersonating' => \App\Http\Middleware\BlockWhileImpersonating::class,
        ]);

        // Terminable middleware: tracks login activity after response is sent
        $middleware->web(append: [
            \App\Http\Middleware\EnsureNotArchived::class,
            \App\Http\Middleware\TrackLoginActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
