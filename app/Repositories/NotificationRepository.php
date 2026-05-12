<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class NotificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listForUser(int $userId, ?string $type = null): array
    {
        $sql = 'SELECT n.*, t.title AS task_title, t.plant_id, t.due_date AS task_due
                FROM notifications n
                LEFT JOIN care_tasks t ON t.id = n.task_id
                WHERE n.user_id = :uid';
        $params = [':uid' => $userId];
        if ($type) {
            $sql .= ' AND n.type = :type';
            $params[':type'] = $type;
        }
        $sql .= ' ORDER BY n.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO notifications (user_id, task_id, title, message, type, is_read, created_at)
                VALUES (:uid, :tid, :title, :msg, :type, 0, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid' => $data['user_id'],
            ':tid' => $data['task_id'] ?? null,
            ':title' => $data['title'],
            ':msg' => $data['message'] ?? null,
            ':type' => $data['type'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public function markAllRead(int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0');
        return $stmt->execute([':uid' => $userId]);
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function refreshTaskNotifications(int $userId): void
    {
        // 1) Wyczyść stare task-notifications dla tego użytkownika (te dotyczące tasków)
        $stmt = $this->db->prepare('DELETE FROM notifications WHERE user_id = :uid AND task_id IS NOT NULL');
        $stmt->execute([':uid' => $userId]);

        // 2) Utwórz "today"
        $stmt = $this->db->prepare("SELECT id, title, plant_id FROM care_tasks
                                    WHERE user_id = :uid AND status = 'pending'
                                      AND DATE(due_date) = CURDATE()");
        $stmt->execute([':uid' => $userId]);
        foreach ($stmt->fetchAll() as $t) {
            $this->create([
                'user_id' => $userId, 'task_id' => $t['id'],
                'title' => $t['title'], 'message' => 'Zaplanowane na dziś.',
                'type' => 'today',
            ]);
        }
        // 3) Utwórz "overdue"
        $stmt = $this->db->prepare("SELECT id, title, plant_id FROM care_tasks
                                    WHERE user_id = :uid AND status = 'pending'
                                      AND DATE(due_date) < CURDATE()");
        $stmt->execute([':uid' => $userId]);
        foreach ($stmt->fetchAll() as $t) {
            $this->create([
                'user_id' => $userId, 'task_id' => $t['id'],
                'title' => $t['title'], 'message' => 'Zaległe zadanie pielęgnacyjne.',
                'type' => 'overdue',
            ]);
        }
        // 4) Utwórz "incoming" (najbliższe 3 dni)
        $stmt = $this->db->prepare("SELECT id, title, plant_id FROM care_tasks
                                    WHERE user_id = :uid AND status = 'pending'
                                      AND DATE(due_date) > CURDATE()
                                      AND DATE(due_date) <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
        $stmt->execute([':uid' => $userId]);
        foreach ($stmt->fetchAll() as $t) {
            $this->create([
                'user_id' => $userId, 'task_id' => $t['id'],
                'title' => $t['title'], 'message' => 'Nadchodzące zadanie.',
                'type' => 'incoming',
            ]);
        }
    }
}
