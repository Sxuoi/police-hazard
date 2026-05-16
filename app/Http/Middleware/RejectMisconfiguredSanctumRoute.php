<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Design §14 — Runtime guard for misconfigured Sanctum routes (R11.6).
 *
 * In production, if a route was flagged at boot time as using auth:sanctum
 * without saker-context, this middleware renders an RFC 7807 problem+json
 * response (HTTP 500) to prevent the request from reaching the controller.
 *
 * Registered as the first global middleware in the `api` group.
 */
class RejectMisconfiguredSanctumRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $flagged = app('ph.sanctum_routes_missing_saker_context') ?? [];

        $uri = optional($request->route())->uri();

        if ($uri && in_array($uri, $flagged, true)) {
            return new JsonResponse([
                'type' => 'https://policehazard.local/errors/MIDDLEWARE_MISCONFIGURED',
                'title' => 'Route middleware misconfigured',
                'status' => 500,
                'detail' => 'This route is missing a required security middleware.',
                'reason_code' => 'MIDDLEWARE_MISCONFIGURED',
                'request_id' => $request->attributes->get('request_id'),
            ], 500, ['Content-Type' => 'application/problem+json']);
        }

        return $next($request);
    }
}
