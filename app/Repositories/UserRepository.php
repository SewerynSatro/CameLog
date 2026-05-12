<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (name, email, password_hash, role, status, bio, created_at, updated_at)
                VALUES (:name, :email, :password_hash, :role, :status, :bio, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role' => $data['role'] ?? 'user',
            ':status' => $data['status'] ?? 'active',
            ':bio' => $data['bio'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'email', 'bio', 'password_hash', 'role', 'status', 'preferences'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        if (empty($sets)) return false;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function listAll(?string $search = null, ?string $status = null, ?string $role = null): array
    {
        $sql = 'SELECT id, name, email, role, status, bio, created_at, updated_at FROM users WHERE 1=1';
        $params = [];
        if ($search) {
            $sql .= ' AND (name LIKE :s OR email LIKE :s)';
            $params[':s'] = '%' . $search . '%';
        }
        if ($status) {
            $sql .= ' AND status = :status';
            $params[':status'] = $status;
        }
        if ($role) {
            $sql .= ' AND role = :role';
            $params[':role'] = $role;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function stats(): array
    {
        $total = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $active = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
        $newWeek = (int) $this->db->query('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
        return ['total' => $total, 'active' => $active, 'new_this_week' => $newWeek];
    }
}
