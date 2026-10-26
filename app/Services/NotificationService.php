<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Creates in-app notifications. Mail delivery is attempted through the
 * configured mailer (which is `log` by default in local development).
 */
class NotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function notify(User|int $user, string $type, string $title, string $message, array $data = []): AppNotification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = AppNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => 'database',
        ]);

        return $notification;
    }

    /**
     * Notify several users at once.
     *
     * @param  iterable<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    public function notifyMany(iterable $users, string $type, string $title, string $message, array $data = []): void
    {
        foreach ($users as $user) {
            $this->notify($user, $type, $title, $message, $data);
        }
    }
}
