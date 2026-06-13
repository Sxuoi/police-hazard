<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function createForUser(string $recipientId, array $data): Notification
    {
        return Notification::create(array_merge($data, [
            'recipient_id' => $recipientId,
        ]));
    }

    public function getUnread(string $recipientId): Collection
    {
        return Notification::where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();
    }

    public function markAsRead(string $notificationId): void
    {
        // Direct DB update to bypass any scope issues
        \DB::table('notifications')
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(string $recipientId): int
    {
        return Notification::where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}
