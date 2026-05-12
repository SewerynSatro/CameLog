<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Response::unauthorized('Musisz się zalogować, aby kontynuować.');
        }

        // Sprawdź czy użytkownik nie jest zablokowany
        $stmt = Database::getInstance()->prepare('SELECT status FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => Auth::id()]);
        $row = $stmt->fetch();
        if (!$row) {
            Auth::logout();
            Response::unauthorized('Konto nie istnieje.');
        }
        if ($row['status'] === 'blocked') {
            Auth::logout();
            Response::forbidden('Twoje konto zostało zablokowane przez administratora.');
        }
    }
}
