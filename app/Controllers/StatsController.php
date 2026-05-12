<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\StatsService;

class StatsController
{
    private StatsService $service;

    public function __construct()
    {
        $this->service = new StatsService();
    }

    public function overview(Request $req): void
    {
        $userId = Auth::id();
        Response::json(['overview' => $this->service->overview($userId)]);
    }
}
