<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGodAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isGodAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. Halaman Peta Panas hanya dapat diakses oleh God Admin.'
                ], 403);
            }
            abort(403, 'Unauthorized. Halaman Peta Panas hanya dapat diakses oleh God Admin.');
        }

        return $next($request);
    }
}
