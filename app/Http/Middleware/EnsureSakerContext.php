<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PRD §4.3 — Layer 3: Resource-level validation.
 * Validates that the authenticated user's saker_id matches
 * the resource being accessed. Returns 403 on mismatch.
 *
 * Applied to all admin and API routes that access tenant-scoped resources.
 *
 * Supports both session-based auth (admin web) and Sanctum token auth
 * (officer mobile API). When using auth:sanctum, the saker_id is resolved
 * from $request->user()?->saker_id.
 */
class EnsureSakerContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        // God Admin bypasses tenant validation (they have cross-tenant access)
        if ($user->isGodAdmin()) {
            return $next($request);
        }

        // Ensure the user has a saker_id (required for tenant scoping)
        $userSakerId = $user->saker_id;

        if (! $userSakerId) {
            abort(403, 'User tidak memiliki konteks Satuan Kerja.');
        }

        // Stash the resolved saker_id on the request for downstream use
        // (works for both session auth and Sanctum token auth)
        $request->attributes->set('saker_id', $userSakerId);

        // Check if a saker_id is present in the route or request
        $resourceSakerId = $request->route('saker_id')
            ?? $request->input('saker_id')
            ?? null;

        if ($resourceSakerId && $resourceSakerId !== $userSakerId) {
            // Log unauthorized cross-tenant access attempt
            abort(403, 'Akses lintas Satuan Kerja tidak diizinkan.');
        }

        return $next($request);
    }
}
