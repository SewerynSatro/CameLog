<?php
namespace App\Services;

use App\Repositories\NotificationRepository;

class ReminderService
{
    private NotificationRepository $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationRepository();
    }

    public function listForUser(int $userId, ?string $type = null): array
    {
        // Synchronizuj z aktualnym stanem tasków.
        $this->notifications->refreshTaskNotifications($userId);
        return $this->notifications->listForUser($userId, $type);
    }

    public function markRead(int $id, int $userId): bool
    {
        return $this->notifications->markRead($id, $userId);
    }

    public function markAllRead(int $userId): bool
    {
        return $this->notifications->markAllRead($userId);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCount($userId);
    }
}
