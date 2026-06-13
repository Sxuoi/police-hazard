<?php

namespace App\Support\Dtos;

use App\Models\User;

/**
 * OfficerProfileDto — Immutable value object for officer profile in API responses.
 * Returned as part of the login response and other officer-facing endpoints.
 */
final readonly class OfficerProfileDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $nrp,
        public array $saker,
        public ?string $avatarUrl,
    ) {}

    /**
     * Build from a User model with loaded saker relationship.
     */
    public static function fromUser(User $user): self
    {
        $saker = $user->saker;

        return new self(
            id: $user->id,
            name: $user->name,
            nrp: $user->nrp,
            saker: [
                'id' => $saker->id,
                'code' => $saker->code,
                'name' => $saker->name,
                'type' => $saker->type,
            ],
            avatarUrl: $user->avatar_path,
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nrp' => $this->nrp,
            'saker' => $this->saker,
            'avatar_url' => $this->avatarUrl,
        ];
    }
}
