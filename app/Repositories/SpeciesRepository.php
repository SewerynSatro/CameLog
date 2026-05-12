<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class SpeciesRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM species WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByExternalId($externalId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM species WHERE external_api_id = :eid LIMIT 1');
        $stmt->execute([':eid' => (string) $externalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function search(string $query, int $limit = 10): array
    {
        $stmt = $this->db->prepare('SELECT * FROM species WHERE common_name LIKE :q OR scientific_name LIKE :q LIMIT :limit');
        $stmt->bindValue(':q', '%' . $query . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function upsert(array $data): int
    {
        $existing = $data['external_api_id'] ? $this->findByExternalId($data['external_api_id']) : null;
        if ($existing) {
            $stmt = $this->db->prepare('UPDATE species SET common_name = :cn, scientific_name = :sn,
                                        care_level = :cl, watering_info = :wi, sunlight_info = :si,
                                        climate_info = :ci, raw_api_data = :raw, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':cn' => $data['common_name'],
                ':sn' => $data['scientific_name'] ?? null,
                ':cl' => $data['care_level'] ?? null,
                ':wi' => $data['watering_info'] ?? null,
                ':si' => $data['sunlight_info'] ?? null,
                ':ci' => $data['climate_info'] ?? null,
                ':raw' => is_array($data['raw_api_data'] ?? null) ? json_encode($data['raw_api_data']) : ($data['raw_api_data'] ?? null),
                ':id' => $existing['id'],
            ]);
            return (int) $existing['id'];
        }
        $stmt = $this->db->prepare('INSERT INTO species (external_api_id, common_name, scientific_name, care_level,
                                    watering_info, sunlight_info, climate_info, raw_api_data, created_at, updated_at)
                                    VALUES (:eid, :cn, :sn, :cl, :wi, :si, :ci, :raw, NOW(), NOW())');
        $stmt->execute([
            ':eid' => $data['external_api_id'] ?? null,
            ':cn' => $data['common_name'],
            ':sn' => $data['scientific_name'] ?? null,
            ':cl' => $data['care_level'] ?? null,
            ':wi' => $data['watering_info'] ?? null,
            ':si' => $data['sunlight_info'] ?? null,
            ':ci' => $data['climate_info'] ?? null,
            ':raw' => is_array($data['raw_api_data'] ?? null) ? json_encode($data['raw_api_data']) : ($data['raw_api_data'] ?? null),
        ]);
        return (int) $this->db->lastInsertId();
    }
}
