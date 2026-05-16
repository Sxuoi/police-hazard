<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router): void
    {
        $this->registerOfficerApiRateLimiters();

        // Design §14 — Fail-fast middleware assertion (R11.6).
        // After routes are loaded, verify every route using auth:sanctum
        // also has the saker-context middleware applied.
        $this->app->booted(function () use ($router) {
            $misconfigured = [];

            foreach ($router->getRoutes() as $route) {
                $mw = $route->gatherMiddleware();

                if (in_array('auth:sanctum', $mw, true) && ! in_array('saker-context', $mw, true)) {
                    $msg = "Route [{$route->uri()}] uses auth:sanctum without saker-context middleware.";

                    if (app()->environment('production')) {
                        Log::critical($msg);
                        $misconfigured[] = $route->uri();
                    } else {
                        throw new \DomainException($msg);
                    }
                }
            }

            // Stash flagged route URIs for the runtime guard (production branch).
            app()->instance('ph.sanctum_routes_missing_saker_context', $misconfigured);
        });
    }

    /**
     * Design §12 — Officer API rate limiters (R1.6, R3.16, R4.17).
     */
    private function registerOfficerApiRateLimiters(): void
    {
        RateLimiter::for('officer-login', fn (Request $r) => [
            Limit::perMinutes(
                (int) config('policehazard.auth.lockout_minutes', 15),
                (int) config('policehazard.auth.max_login_attempts', 5),
            )->by((string) ($r->input('nrp') ?? '').'|'.$r->ip()),
        ]);

        RateLimiter::for('officer-checkin', fn (Request $r) => [
            Limit::perMinute((int) config('policehazard.auth.checkin_rate_limit', 10))
                ->by('checkin:'.($r->user()?->id ?? $r->ip())),
        ]);

        RateLimiter::for('officer-bypass', fn (Request $r) => [
            Limit::perMinute((int) config('policehazard.auth.bypass_rate_limit', 5))
                ->by('bypass:'.($r->user()?->id ?? $r->ip())),
        ]);
    }
}
