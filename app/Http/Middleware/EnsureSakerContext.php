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
 */
class EnsureSakerContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Authentication required.');
        }

        // God Admin bypasses tenant validation (they have cross-tenant access)
        if ($user->isGodAdmin()) {
            return $next($request);
        }

        // Check if a saker_id is present in the route or request
        $resourceSakerId = $request->route('saker_id')
            ?? $request->input('saker_id')
            ?? null;

        if ($resourceSakerId && $resourceSakerId !== $user->saker_id) {
            // Log unauthorized cross-tenant access attempt
            // AuditService will handle this in Phase 2
            abort(403, 'Akses lintas Satuan Kerja tidak diizinkan.');
        }

        return $next($request);
    }
}
