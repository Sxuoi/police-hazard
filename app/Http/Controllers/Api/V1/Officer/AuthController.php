<?php

namespace App\Http\Controllers\Api\V1\Officer;

use App\Actions\AuthenticateOfficerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Officer\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Officer API — Authentication controller (R1).
 * Thin orchestrator: validates via FormRequest → invokes Action → returns JSON.
 */
class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/login (R1.1, R1.2).
     */
    public function login(LoginRequest $request, AuthenticateOfficerAction $action): JsonResponse
    {
        $payload = $action(
            $request->validated('nrp'),
            $request->validated('password'),
            $request,
        );

        return response()->json($payload, 200);
    }

    /**
     * POST /api/v1/auth/logout (R1.8).
     * Revokes the current Sanctum token.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
