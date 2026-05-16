<?php

use App\Exceptions\ApiProblemRenderer;
use App\Http\Middleware\EnsureSakerContext;
use App\Http\Middleware\RejectMisconfiguredSanctumRoute;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SetGodAdminContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware for both web and api groups
        $middleware->appendToGroup('web', [
            SecurityHeadersMiddleware::class,
        ]);
        $middleware->appendToGroup('api', [
            RejectMisconfiguredSanctumRoute::class,
            SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'saker.context' => EnsureSakerContext::class,
            'saker-context' => EnsureSakerContext::class,
            'god.admin' => SetGodAdminContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Design §9.9 — Render CheckinException / BypassException as
        // RFC 7807 problem+json responses for the Officer API.
        $exceptions->render(function (Throwable $e, Request $request) {
            return app(ApiProblemRenderer::class)($e, $request);
        });
    })->create();
