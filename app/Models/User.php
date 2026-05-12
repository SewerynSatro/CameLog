<?php
namespace App\Models;

/**
 * User - lekki model danych. Logika dostępu jest w UserRepository.
 */
class User
{
    public int $id;
    public string $name;
    public string $email;
    public string $role = 'user';
    public string $status = 'active';
    public ?string $bio = null;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $u = new self();
        $u->id = (int) ($data['id'] ?? 0);
        $u->name = $data['name'] ?? '';
        $u->email = $data['email'] ?? '';
        $u->role = $data['role'] ?? 'user';
        $u->status = $data['status'] ?? 'active';
        $u->bio = $data['bio'] ?? null;
        $u->createdAt = $data['created_at'] ?? null;
        $u->updatedAt = $data['updated_at'] ?? null;
        return $u;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'bio' => $this->bio,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
