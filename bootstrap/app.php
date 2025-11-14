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
            'IsAdmin' => \App\Http\Middleware\IsAdmin::class,
            'IsCoordinador' => \App\Http\Middleware\IsCoordinador::class,
            'IsAdminOrCoordinador' => \App\Http\Middleware\IsAdminOrCoordinador::class,
            'IsAutoridad' => \App\Http\Middleware\IsAutoridad::class,
            'IsDocente' => \App\Http\Middleware\IsDocente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado. Token inválido o expirado.'
                ], 401);
            }
        });
    })->create();
