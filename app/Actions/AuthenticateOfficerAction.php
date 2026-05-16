<?php

namespace App\Actions;

use App\Exceptions\Checkin\CheckinException;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Dtos\OfficerProfileDto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * AuthenticateOfficerAction — Design §2.5, R1.
 * Authenticates an officer via NRP + password and issues a Sanctum PAT.
 *
 * On failure: writes OFFICER_LOGIN_FAILED audit event.
 * On success: creates Sanctum token, updates last_login_at, writes OFFICER_LOGIN_SUCCESS.
 */
class AuthenticateOfficerAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * @throws CheckinException
     */
    public function __invoke(string $nrp, string $password, Request $request): array
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $user = User::withoutGlobalScopes()->where('nrp', $nrp)->first();

        // R1.3 — NRP not found or password wrong → INVALID_CREDENTIALS (401)
        if (! $user || ! Hash::check($password, $user->password)) {
            $this->auditLoginFailed($nrp, $ip, $userAgent, 'INVALID_CREDENTIALS', $user);

            throw new class extends CheckinException
            {
                public function __construct()
                {
                    parent::__construct(
                        reasonCode: 'INVALID_CREDENTIALS',
                        httpStatus: 401,
                        bypassEligible: false,
                    );
                }
            };
        }

        // R1.4 — User inactive → ACCOUNT_DISABLED (403)
        if (! $user->is_active) {
            $this->auditLoginFailed($nrp, $ip, $userAgent, 'ACCOUNT_DISABLED', $user);

            throw new class extends CheckinException
            {
                public function __construct()
                {
                    parent::__construct(
                        reasonCode: 'ACCOUNT_DISABLED',
                        httpStatus: 403,
                        bypassEligible: false,
                    );
                }
            };
        }

        // R1.5 — Role != officer → ACCOUNT_DISABLED (403, don't leak role info)
        if ($user->role !== 'officer') {
            $this->auditLoginFailed($nrp, $ip, $userAgent, 'ACCOUNT_DISABLED', $user);

            throw new class extends CheckinException
            {
                public function __construct()
                {
                    parent::__construct(
                        reasonCode: 'ACCOUNT_DISABLED',
                        httpStatus: 403,
                        bypassEligible: false,
                    );
                }
            };
        }

        // Success — create Sanctum token
        $expiresAt = now()->addHours(
            (int) config('policehazard.auth.token_expiry_hours', 12)
        );

        $token = $user->createToken('officer-mobile', [], $expiresAt);

        // Update last_login_at without triggering model events
        $user->timestamps = false;
        $user->last_login_at = now();
        $user->saveQuietly();
        $user->timestamps = true;

        // R1.9 — Audit success
        $this->auditService->log('OFFICER_LOGIN_SUCCESS', $user, [
            'actor_ip' => $ip,
            'actor_user_agent' => $userAgent,
        ]);

        // Load saker for the profile DTO
        $user->loadMissing('saker');

        return [
            'token' => $token->plainTextToken,
            'token_expires_at' => $token->accessToken->expires_at->toIso8601String(),
            'officer' => OfficerProfileDto::fromUser($user)->toArray(),
        ];
    }

    /**
     * Write OFFICER_LOGIN_FAILED audit event.
     * R1.10 — includes nrp, actor_ip, actor_user_agent, reason_code. Never includes password.
     */
    private function auditLoginFailed(
        string $nrp,
        ?string $ip,
        ?string $userAgent,
        string $reasonCode,
        ?User $user,
    ): void {
        $this->auditService->log('OFFICER_LOGIN_FAILED', $user, [
            'nrp' => $nrp,
            'actor_ip' => $ip,
            'actor_user_agent' => $userAgent,
            'reason_code' => $reasonCode,
        ]);
    }
}
