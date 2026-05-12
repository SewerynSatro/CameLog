<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\AuthService;

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    public function register(Request $req): void
    {
        $data = $req->all();
        $validator = (new Validator($data))
            ->required('name')
            ->min('name', 2)
            ->required('email')
            ->email('email')
            ->required('password')
            ->min('password', 6);
        if ($validator->fails()) {
            Response::error('Nieprawidłowe dane', 422, ['errors' => $validator->errors()]);
        }
        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? null) && isset($data['password_confirm'])) {
            Response::error('Hasła nie są zgodne', 422, ['errors' => ['password_confirm' => 'Hasła nie są zgodne']]);
        }
        $r = $this->service->register($data['name'], $data['email'], $data['password']);
        if (!$r['ok']) Response::error($r['message'], 409);
        Response::json(['user' => $r['user']]);
    }

    public function login(Request $req): void
    {
        $data = $req->all();
        $v = (new Validator($data))->required('email')->email('email')->required('password');
        if ($v->fails()) {
            Response::error('Podaj email i hasło', 422, ['errors' => $v->errors()]);
        }
        $r = $this->service->login($data['email'], $data['password']);
        if (!$r['ok']) {
            Response::error($r['message'], $r['status'] ?? 401);
        }
        Response::json(['user' => $r['user']]);
    }

    public function logout(Request $req): void
    {
        $this->service->logout();
        Response::json(['ok' => true]);
    }

    public function me(Request $req): void
    {
        $u = $this->service->currentUser();
        if (!$u) Response::unauthorized();
        Response::json(['user' => $u]);
    }

    public function updateProfile(Request $req): void
    {
        $userId = Auth::id();
        $data = $req->all();
        $allowed = [];
        if (!empty($data['name'])) $allowed['name'] = $data['name'];
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                Response::error('Nieprawidłowy email', 422);
            }
            $allowed['email'] = $data['email'];
        }
        if (isset($data['bio'])) $allowed['bio'] = $data['bio'];
        $repo = new \App\Repositories\UserRepository();
        $repo->update($userId, $allowed);
        $u = $this->service->currentUser();
        Response::json(['user' => $u]);
    }

    public function changePassword(Request $req): void
    {
        $userId = Auth::id();
        $data = $req->all();
        $v = (new Validator($data))->required('current_password')->required('new_password')->min('new_password', 6);
        if ($v->fails()) Response::error('Wypełnij wszystkie pola hasła', 422, ['errors' => $v->errors()]);

        $r = $this->service->changePassword($userId, $data['current_password'], $data['new_password']);
        if (!$r['ok']) Response::error($r['message'], 400);
        Response::json(['ok' => true]);
    }
}
