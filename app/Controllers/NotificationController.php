<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\ReminderService;

class NotificationController
{
    private ReminderService $service;

    public function __construct()
    {
        $this->service = new ReminderService();
    }

    public function index(Request $req): void
    {
        $userId = Auth::id();
        Response::json([
            'notifications' => $this->service->listForUser($userId),
            'unread_count' => $this->service->unreadCount($userId),
        ]);
    }

    public function today(Request $req): void
    {
        $userId = Auth::id();
        Response::json(['notifications' => $this->service->listForUser($userId, 'today')]);
    }

    public function incoming(Request $req): void
    {
        $userId = Auth::id();
        Response::json(['notifications' => $this->service->listForUser($userId, 'incoming')]);
    }

    public function markRead(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        Response::json(['ok' => $this->service->markRead($id, $userId)]);
    }

    public function markAllRead(Request $req): void
    {
        $userId = Auth::id();
        Response::json(['ok' => $this->service->markAllRead($userId)]);
    }
}
