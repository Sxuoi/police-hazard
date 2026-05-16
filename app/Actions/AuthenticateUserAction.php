<?php

namespace App\Actions;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * PRD §13.1 — Authenticates admin users (god_admin, saker_admin) via NRP + password.
 * Officers authenticate via Sanctum token (Phase 3).
 */
class AuthenticateUserAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(string $nrp, string $password, string $ip, string $userAgent): User
    {
        $user = User::withoutGlobalScopes()->where('nrp', $nrp)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            if ($user) {
                $this->auditService->log('USER_LOGIN_FAILED', $user, [
                    'ip' => $ip,
                    'reason' => 'invalid_credentials',
                ]);
            }

            throw ValidationException::withMessages([
                'nrp' => [__('NRP atau password salah.')],
            ]);
        }

        if (! $user->is_active) {
            $this->auditService->log('USER_LOGIN_FAILED', $user, [
                'ip' => $ip,
                'reason' => 'account_disabled',
            ]);

            throw ValidationException::withMessages([
                'nrp' => [__('Akun Anda telah dinonaktifkan. Hubungi administrator.')],
            ]);
        }

        if ($user->isOfficer()) {
            throw ValidationException::withMessages([
                'nrp' => [__('Akun Officer hanya dapat login melalui aplikasi mobile.')],
            ]);
        }

        // Login via session
        Auth::login($user);

        // Update last_login_at
        $user->timestamps = false;
        $user->last_login_at = now();
        $user->saveQuietly();
        $user->timestamps = true;

        $this->auditService->log('USER_LOGIN', $user, [
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);

        return $user;
    }
}
