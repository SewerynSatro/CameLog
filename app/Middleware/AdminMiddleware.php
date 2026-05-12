<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class AdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Response::unauthorized();
        }
        if (!Auth::isAdmin()) {
            Response::forbidden('Wymagane uprawnienia administratora.');
        }
    }
}
