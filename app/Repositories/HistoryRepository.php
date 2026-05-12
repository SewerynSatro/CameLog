<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class HistoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listForPlant(int $plantId, int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM care_history WHERE plant_id = :pid AND user_id = :uid
                                    ORDER BY performed_at DESC LIMIT :lim');
        $stmt->bindValue(':pid', $plantId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listForUser(int $userId, int $limit = 200): array
    {
        $stmt = $this->db->prepare('SELECT * FROM care_history WHERE user_id = :uid
                                    ORDER BY performed_at DESC LIMIT :lim');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO care_history (user_id, plant_id, task_id, type, note, performed_at)
                VALUES (:uid, :pid, :tid, :type, :note, :performed_at)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid' => $data['user_id'],
            ':pid' => $data['plant_id'],
            ':tid' => $data['task_id'] ?? null,
            ':type' => $data['type'],
            ':note' => $data['note'] ?? null,
            ':performed_at' => $data['performed_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function statsForUser(int $userId, int $days = 30): array
    {
        $stmt = $this->db->prepare("SELECT type, COUNT(*) AS cnt FROM care_history
                                    WHERE user_id = :uid AND performed_at >= DATE_SUB(NOW(), INTERVAL :d DAY)
                                    GROUP BY type");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':d', $days, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $out = ['watering' => 0, 'fertilizing' => 0, 'pruning' => 0, 'repotting' => 0, 'custom' => 0, 'misting' => 0];
        foreach ($rows as $r) {
            $out[$r['type']] = (int) $r['cnt'];
        }
        $out['total'] = array_sum($out);
        return $out;
    }

    public function dailyActivity(int $userId, int $days = 30): array
    {
        $stmt = $this->db->prepare("SELECT DATE(performed_at) AS d, COUNT(*) AS cnt
                                    FROM care_history
                                    WHERE user_id = :uid AND performed_at >= DATE_SUB(CURDATE(), INTERVAL :d DAY)
                                    GROUP BY DATE(performed_at)
                                    ORDER BY d ASC");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':d', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function topPlant(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT p.id, p.name, COUNT(h.id) AS cnt
                                    FROM care_history h
                                    JOIN plants p ON p.id = h.plant_id
                                    WHERE h.user_id = :uid
                                    GROUP BY p.id, p.name
                                    ORDER BY cnt DESC LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
