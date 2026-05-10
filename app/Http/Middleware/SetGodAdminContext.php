<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PRD §4.3, §20.1 — Sets God Admin bypass flag.
 * When the authenticated user is a God Admin, sets the container flag
 * `saker.bypass = true` so that SakerScope skips tenant filtering.
 *
 * This is the ONLY approved way to bypass SakerScope.
 * Disabling the scope directly is prohibited.
 */
class SetGodAdminContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isGodAdmin()) {
            app()->instance('saker.bypass', true);
        } else {
            app()->instance('saker.bypass', false);
        }

        return $next($request);
    }
}
