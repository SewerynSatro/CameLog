<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class TaskRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listForUser(int $userId, array $filters = []): array
    {
        $sql = 'SELECT t.*, p.name AS plant_name, p.location AS plant_location
                FROM care_tasks t
                JOIN plants p ON p.id = t.plant_id
                WHERE t.user_id = :uid';
        $params = [':uid' => $userId];

        if (!empty($filters['type'])) {
            $sql .= ' AND t.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND t.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['plant_id'])) {
            $sql .= ' AND t.plant_id = :pid';
            $params[':pid'] = $filters['plant_id'];
        }
        if (!empty($filters['due'])) {
            if ($filters['due'] === 'today') {
                $sql .= ' AND DATE(t.due_date) = CURDATE() AND t.status = "pending"';
            } elseif ($filters['due'] === 'overdue') {
                $sql .= ' AND DATE(t.due_date) < CURDATE() AND t.status = "pending"';
            } elseif ($filters['due'] === 'incoming') {
                $sql .= ' AND DATE(t.due_date) > CURDATE() AND t.status = "pending"';
            }
        }
        $sql .= ' ORDER BY t.due_date ASC, t.priority DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT t.*, p.name AS plant_name, p.location AS plant_location
                                    FROM care_tasks t
                                    JOIN plants p ON p.id = t.plant_id
                                    WHERE t.id = :id AND t.user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO care_tasks
                (user_id, plant_id, type, title, description, due_date, status, repeat_interval_days, priority, created_at, updated_at)
                VALUES (:uid, :pid, :type, :title, :desc, :due, :status, :rep, :pr, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid' => $data['user_id'],
            ':pid' => $data['plant_id'],
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':desc' => $data['description'] ?? null,
            ':due' => $data['due_date'],
            ':status' => $data['status'] ?? 'pending',
            ':rep' => $data['repeat_interval_days'] ?? null,
            ':pr' => $data['priority'] ?? 'normal',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $allowed = ['type', 'title', 'description', 'due_date', 'status', 'repeat_interval_days', 'priority', 'completed_at'];
        $sets = [];
        $params = [':id' => $id, ':uid' => $userId];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        if (empty($sets)) return false;
        $sql = 'UPDATE care_tasks SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id AND user_id = :uid';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM care_tasks WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public function statsForUser(int $userId): array
    {
        $today = (int) $this->getCount($userId, 'AND DATE(due_date) = CURDATE()');
        $overdue = (int) $this->getCount($userId, 'AND DATE(due_date) < CURDATE() AND status = "pending"');
        $weekDone = (int) $this->getCount($userId, 'AND status = "done" AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $upcomingWeek = (int) $this->getCount($userId, 'AND status = "pending" AND DATE(due_date) > CURDATE() AND DATE(due_date) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)');
        return [
            'today' => $today,
            'overdue' => $overdue,
            'week_done' => $weekDone,
            'upcoming_week' => $upcomingWeek,
        ];
    }

    private function getCount(int $userId, string $extra): int
    {
        $sql = "SELECT COUNT(*) FROM care_tasks WHERE user_id = :uid $extra";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
