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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'LoginMiddleware'     => \App\Http\Middleware\LoginMiddleware::class,
            'AdminMiddleware'     => \App\Http\Middleware\AdminMiddleware::class,
            'PetugasMiddleware'   => \App\Http\Middleware\PetugasMiddleware::class,
            'SupirMiddleware'     => \App\Http\Middleware\SupirMiddleware::class,
            'ExecutiveMiddleware' => \App\Http\Middleware\ExecutiveMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
