<?php

use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\RequirePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        // Redirect unauthenticated users to /login
        $middleware->redirectGuestsTo(fn () => route('login'));
        // Redirect authenticated users away from guest routes to /dashboard
        $middleware->redirectUsersTo(fn () => route(auth()->user()?->defaultRouteName() ?? 'dashboard'));

        // Custom middleware aliases
        $middleware->alias([
            'user.active' => CheckUserActive::class,
            'permission' => RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return response()->view('errors.404', [], 404);
            }
        });
    })->create();
