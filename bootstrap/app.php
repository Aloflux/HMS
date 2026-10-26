<?php

use App\Http\Middleware\EnsureFacilityOnboarded;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetFacilityContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetFacilityContext::class,
            SecurityHeaders::class,
        ]);

        // Route models must resolve after the tenant is known, and after auth.
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            SetFacilityContext::class,
        );

        $middleware->alias([
            'permission' => EnsurePermission::class,
            'onboarded' => EnsureFacilityOnboarded::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
