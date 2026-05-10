<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    public function createForUser(string $recipientId, array $data): \App\Models\Notification;
    public function getUnread(string $recipientId): Collection;
    public function markAsRead(string $notificationId): void;
    public function getUnreadCount(string $recipientId): int;
}
