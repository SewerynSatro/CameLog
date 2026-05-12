<?php
namespace App\Services;

use App\Core\Auth;
use App\Repositories\UserRepository;

class AuthService
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function register(string $name, string $email, string $password): array
    {
        $existing = $this->users->findByEmail($email);
        if ($existing) {
            return ['ok' => false, 'message' => 'Konto z tym adresem email już istnieje.'];
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $id = $this->users->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => $hash,
            'role' => 'user',
            'status' => 'active',
        ]);
        Auth::login($id, 'user');
        return ['ok' => true, 'user' => $this->safeUser($this->users->findById($id))];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return ['ok' => false, 'message' => 'Nieprawidłowy email lub hasło.'];
        }
        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Nieprawidłowy email lub hasło.'];
        }
        if ($user['status'] === 'blocked') {
            return ['ok' => false, 'message' => 'Twoje konto jest zablokowane.', 'status' => 403];
        }
        Auth::login((int) $user['id'], $user['role']);
        return ['ok' => true, 'user' => $this->safeUser($user)];
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function currentUser(): ?array
    {
        $id = Auth::id();
        if (!$id) return null;
        $user = $this->users->findById($id);
        return $user ? $this->safeUser($user) : null;
    }

    public function changePassword(int $userId, string $current, string $new): array
    {
        $user = $this->users->findById($userId);
        if (!$user) return ['ok' => false, 'message' => 'Użytkownik nie istnieje.'];
        if (!password_verify($current, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Aktualne hasło jest nieprawidłowe.'];
        }
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $this->users->update($userId, ['password_hash' => $hash]);
        return ['ok' => true];
    }

    private function safeUser(array $u): array
    {
        unset($u['password_hash']);
        return $u;
    }
}
