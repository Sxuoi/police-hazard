<?php

namespace App\Actions;

use App\Models\User;

/**
 * RevokeOfficerTokensAction — Design §2.
 *
 * Revokes all Sanctum tokens for a user. Invoked when is_active flips to false.
 *
 * (R1.14)
 */
final class RevokeOfficerTokensAction
{
    public function __invoke(User $user): void
    {
        $user->tokens()->delete();
    }
}
