<?php

namespace App\Services;

use App\Jobs\SendUserNotificationJob;
use App\Models\UserNotification;

class NotificationService
{
    public function notifyUser(int $userId, string $type, string $title, string $message, array $data = []): UserNotification
    {
        return $this->notifyUserNow($userId, $type, $title, $message, $data);
    }

    public function notifyUserNow(int $userId, string $type, string $title, string $message, array $data = []): UserNotification
    {
        return UserNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function notifyUsers(array $userIds, string $type, string $title, string $message, array $data = []): void
    {
        $unique = collect($userIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        foreach ($unique as $userId) {
            SendUserNotificationJob::dispatch(
                (int) $userId,
                $type,
                $title,
                $message,
                $data
            );
        }
    }
}
