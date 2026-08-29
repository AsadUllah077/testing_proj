<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Routing\Middleware\SubstituteBindings;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->redirectGuestsTo(function () {
            return route('user.login.view');
        });
        $middleware->alias([
            'permission' => CheckPermission::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\TrackIndexUrl::class,
        ]);  

         // 🔹 Register API middleware group (Laravel 11 way)
        // $middleware->group('api', [
        //     EnsureFrontendRequestsAreStateful::class,
        //     'throttle:api',
        //     SubstituteBindings::class,
        // ]);
    })
    ->withExceptions(function (): void {
        //
    })->create();
