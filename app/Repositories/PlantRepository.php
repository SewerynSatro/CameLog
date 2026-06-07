<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class PlantRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listForUser(int $userId, array $filters = []): array
    {
        $sql = 'SELECT p.*, s.common_name AS species_common, s.scientific_name AS species_scientific
                FROM plants p
                LEFT JOIN species s ON s.id = p.species_id
                WHERE p.user_id = :user_id';
        $params = [':user_id' => $userId];

        if (!empty($filters['search'])) {
            $sql .= ' AND (p.name LIKE :search OR p.custom_species_name LIKE :search OR s.common_name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['location'])) {
            $sql .= ' AND p.location = :location';
            $params[':location'] = $filters['location'];
        }
        if (!empty($filters['species'])) {
            $sql .= ' AND (s.common_name LIKE :sp OR p.custom_species_name LIKE :sp)';
            $params[':sp'] = '%' . $filters['species'] . '%';
        }
        $sql .= ' ORDER BY p.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $plants = $stmt->fetchAll();

        // Dołącz główne zdjęcie i najbliższy task dla każdej rośliny
        foreach ($plants as &$plant) {
            $plant['photo'] = $this->getMainPhoto((int) $plant['id']);
            $plant['next_task'] = $this->getNextTask((int) $plant['id']);
        }
        return $plants;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT p.*, s.common_name AS species_common, s.scientific_name AS species_scientific,
                                    s.care_level AS species_care_level, s.watering_info AS species_watering,
                                    s.sunlight_info AS species_sunlight, s.climate_info AS species_climate,
                                    s.raw_api_data AS species_raw
                                    FROM plants p
                                    LEFT JOIN species s ON s.id = p.species_id
                                    WHERE p.id = :id AND p.user_id = :user_id LIMIT 1');
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['photo'] = $this->getMainPhoto((int) $row['id']);
        return $row;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO plants
                (user_id, species_id, name, custom_species_name, location, planted_at, notes,
                 watering_interval_days, fertilizing_interval_days, care_level, api_recommendations_used,
                 health_status, created_at, updated_at)
                VALUES (:user_id, :species_id, :name, :custom_species_name, :location, :planted_at, :notes,
                        :watering_interval_days, :fertilizing_interval_days, :care_level, :api_used,
                        :health_status, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':species_id' => $data['species_id'] ?? null,
            ':name' => $data['name'],
            ':custom_species_name' => $data['custom_species_name'] ?? null,
            ':location' => $data['location'] ?? null,
            ':planted_at' => $data['planted_at'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':watering_interval_days' => $data['watering_interval_days'] ?? null,
            ':fertilizing_interval_days' => $data['fertilizing_interval_days'] ?? null,
            ':care_level' => $data['care_level'] ?? 'easy',
            ':api_used' => !empty($data['api_recommendations_used']) ? 1 : 0,
            ':health_status' => $data['health_status'] ?? 'healthy',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $allowed = ['species_id', 'name', 'custom_species_name', 'location', 'planted_at', 'notes',
                    'watering_interval_days', 'fertilizing_interval_days', 'care_level',
                    'api_recommendations_used', 'health_status'];
        $sets = [];
        $params = [':id' => $id, ':user_id' => $userId];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        if (empty($sets)) return false;
        $sql = 'UPDATE plants SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id AND user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id, int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            $check = $this->db->prepare('SELECT id FROM plants WHERE id = :id AND user_id = :user_id LIMIT 1');
            $check->execute([':id' => $id, ':user_id' => $userId]);
            if (!$check->fetch()) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare('DELETE FROM notifications
                                        WHERE user_id = ?
                                          AND task_id IN (SELECT id FROM care_tasks WHERE plant_id = ? AND user_id = ?)');
            $stmt->execute([$userId, $id, $userId]);

            $stmt = $this->db->prepare('DELETE FROM care_history WHERE plant_id = :id AND user_id = :user_id');
            $stmt->execute([':id' => $id, ':user_id' => $userId]);

            $stmt = $this->db->prepare('DELETE FROM care_tasks WHERE plant_id = :id AND user_id = :user_id');
            $stmt->execute([':id' => $id, ':user_id' => $userId]);

            $stmt = $this->db->prepare('DELETE FROM plant_photos WHERE plant_id = :id');
            $stmt->execute([':id' => $id]);

            $stmt = $this->db->prepare('DELETE FROM plants WHERE id = :id AND user_id = :user_id');
            $stmt->execute([':id' => $id, ':user_id' => $userId]);
            $deleted = $stmt->rowCount() > 0;

            $this->db->commit();
            return $deleted;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function addPhoto(int $plantId, string $filePath, bool $isMain = true): int
    {
        if ($isMain) {
            // odznacz inne jako not-main
            $stmt = $this->db->prepare('UPDATE plant_photos SET is_main = 0 WHERE plant_id = :pid');
            $stmt->execute([':pid' => $plantId]);
        }
        $stmt = $this->db->prepare('INSERT INTO plant_photos (plant_id, file_path, is_main, created_at) VALUES (:pid, :fp, :im, NOW())');
        $stmt->execute([':pid' => $plantId, ':fp' => $filePath, ':im' => $isMain ? 1 : 0]);
        return (int) $this->db->lastInsertId();
    }

    public function getMainPhoto(int $plantId): ?string
    {
        $stmt = $this->db->prepare('SELECT file_path FROM plant_photos WHERE plant_id = :pid ORDER BY is_main DESC, created_at DESC LIMIT 1');
        $stmt->execute([':pid' => $plantId]);
        $row = $stmt->fetch();
        return $row['file_path'] ?? null;
    }

    public function getNextTask(int $plantId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, type, title, due_date, status
                                    FROM care_tasks
                                    WHERE plant_id = :pid AND status = 'pending'
                                    ORDER BY due_date ASC LIMIT 1");
        $stmt->execute([':pid' => $plantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM plants WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
