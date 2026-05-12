<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;

class AdminController
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function listUsers(Request $req): void
    {
        $search = $req->query('search');
        $status = $req->query('status');
        $role = $req->query('role');
        Response::json([
            'users' => $this->users->listAll($search, $status, $role),
            'stats' => $this->users->stats(),
        ]);
    }

    public function block(Request $req): void
    {
        $id = (int) $req->param('id');
        if ($id === Auth::id()) Response::error('Nie możesz zablokować własnego konta.', 400);
        $this->users->update($id, ['status' => 'blocked']);
        Response::json(['ok' => true]);
    }

    public function unblock(Request $req): void
    {
        $id = (int) $req->param('id');
        $this->users->update($id, ['status' => 'active']);
        Response::json(['ok' => true]);
    }

    public function destroy(Request $req): void
    {
        $id = (int) $req->param('id');
        if ($id === Auth::id()) Response::error('Nie możesz usunąć własnego konta.', 400);
        Response::json(['ok' => $this->users->delete($id)]);
    }
}
