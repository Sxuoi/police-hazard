<?php

namespace App\Services;

use App\Repositories\Contracts\NotificationRepositoryInterface;

/**
 * NotificationService — PRD §11.1, §11.2.
 * Dispatches notifications to users via in-app, email, and SMS channels.
 *
 * Stub — full implementation in Phase 3.
 */
class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepo,
    ) {}

    /**
     * Send an in-app notification to a user.
     */
    public function notifyUser(
        string $recipientId,
        string $sakerId,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?array $payload = null,
    ): void {
        $this->notificationRepo->createForUser($recipientId, [
            'saker_id'   => $sakerId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'action_url' => $actionUrl,
            'payload'    => $payload,
            'created_at' => now(),
        ]);
    }

    /**
     * Send notifications to all Saker Admins for a given Saker.
     * Used for bypass requests, spoofing alerts, etc.
     */
    public function notifySakerAdmins(
        string $sakerId,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?array $payload = null,
    ): void {
        // TODO: Query all saker_admin users for this Saker and dispatch
    }
}
