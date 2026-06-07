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
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['id'] = (int) $item['id'];
            $item['user_id'] = (int) $item['user_id'];
            $item['task_id'] = $item['task_id'] !== null ? (int) $item['task_id'] : null;
            $item['plant_id'] = $item['plant_id'] !== null ? (int) $item['plant_id'] : null;
            $item['is_read'] = (int) $item['is_read'];
        }
        unset($item);

        return $items;
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
        $stmt = $this->db->prepare("SELECT id, title, due_date
                                    FROM care_tasks
                                    WHERE user_id = :uid
                                      AND status = 'pending'
                                      AND DATE(due_date) <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
        $stmt->execute([':uid' => $userId]);

        $desired = [];
        foreach ($stmt->fetchAll() as $task) {
            $dueDate = substr((string) $task['due_date'], 0, 10);
            $today = date('Y-m-d');
            $type = null;
            $message = null;

            if ($dueDate < $today) {
                $type = 'overdue';
                $message = 'Zaległe zadanie pielęgnacyjne.';
            } elseif ($dueDate === $today) {
                $type = 'today';
                $message = 'Zaplanowane na dziś.';
            } else {
                $type = 'incoming';
                $message = 'Nadchodzące zadanie.';
            }

            $desired[(int) $task['id']] = [
                'task_id' => (int) $task['id'],
                'title' => $task['title'],
                'message' => $message,
                'type' => $type,
            ];
        }

        if (empty($desired)) {
            $stmt = $this->db->prepare('DELETE FROM notifications WHERE user_id = :uid AND task_id IS NOT NULL');
            $stmt->execute([':uid' => $userId]);
            return;
        }

        $ids = array_keys($desired);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Usuń tylko powiadomienia tasków, które już nie powinny być widoczne.
        $delete = $this->db->prepare("DELETE FROM notifications
                                     WHERE user_id = ?
                                       AND task_id IS NOT NULL
                                       AND task_id NOT IN ($placeholders)");
        $delete->execute(array_merge([$userId], $ids));

        foreach ($desired as $notification) {
            $existing = $this->db->prepare('SELECT id, type FROM notifications
                                           WHERE user_id = :uid AND task_id = :tid
                                           ORDER BY id ASC');
            $existing->execute([
                ':uid' => $userId,
                ':tid' => $notification['task_id'],
            ]);
            $rows = $existing->fetchAll();

            if (!empty($rows)) {
                $keepId = (int) $rows[0]['id'];
                $update = $this->db->prepare('UPDATE notifications
                                             SET title = :title,
                                                 message = :msg,
                                                 type = :type
                                             WHERE id = :id AND user_id = :uid');
                $update->execute([
                    ':title' => $notification['title'],
                    ':msg' => $notification['message'],
                    ':type' => $notification['type'],
                    ':id' => $keepId,
                    ':uid' => $userId,
                ]);

                if (count($rows) > 1) {
                    $duplicates = array_map(fn($row) => (int) $row['id'], array_slice($rows, 1));
                    $dupPlaceholders = implode(',', array_fill(0, count($duplicates), '?'));
                    $cleanup = $this->db->prepare("DELETE FROM notifications WHERE user_id = ? AND id IN ($dupPlaceholders)");
                    $cleanup->execute(array_merge([$userId], $duplicates));
                }
            } else {
                $this->create([
                    'user_id' => $userId,
                    'task_id' => $notification['task_id'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'type' => $notification['type'],
                ]);
            }
        }
    }
}
