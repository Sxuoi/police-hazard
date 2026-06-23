<?php

namespace App\Policies;

use App\Models\Operation;
use App\Models\Saker;

class OperationPolicy
{
    public function viewAny(Saker $user): bool
    {
        return true;
    }

    public function view(Saker $user, Operation $operation): bool
    {
        if ($user->isGodAdmin()) {
            return true;
        }
        return in_array($operation->saker_id, $user->accessibleSakerIds(), true);
    }

    public function create(Saker $user): bool
    {
        return true;
    }

    public function update(Saker $user, Operation $operation): bool
    {
        if ($user->isGodAdmin()) {
            return true;
        }
        return in_array($operation->saker_id, $user->accessibleSakerIds(), true);
    }

    public function delete(Saker $user, Operation $operation): bool
    {
        if ($user->isGodAdmin()) {
            return true;
        }
        return in_array($operation->saker_id, $user->accessibleSakerIds(), true);
    }
}
