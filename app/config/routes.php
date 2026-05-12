<?php
/**
 * Konfiguracja tras dla REST API CameLog.
 * Wszystkie ścieżki używają prefiksu /api.
 */

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Controllers\AuthController;
use App\Controllers\PlantController;
use App\Controllers\SpeciesController;
use App\Controllers\TaskController;
use App\Controllers\NotificationController;
use App\Controllers\StatsController;
use App\Controllers\AdminController;

return function (Router $r): void {
    // Auth
    $r->post('/api/auth/register', [AuthController::class, 'register']);
    $r->post('/api/auth/login', [AuthController::class, 'login']);
    $r->post('/api/auth/logout', [AuthController::class, 'logout']);
    $r->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
    $r->patch('/api/auth/profile', [AuthController::class, 'updateProfile'], [AuthMiddleware::class]);
    $r->patch('/api/auth/password', [AuthController::class, 'changePassword'], [AuthMiddleware::class]);

    // Plants
    $r->get('/api/plants', [PlantController::class, 'index'], [AuthMiddleware::class]);
    $r->get('/api/plants/{id}', [PlantController::class, 'show'], [AuthMiddleware::class]);
    $r->post('/api/plants', [PlantController::class, 'store'], [AuthMiddleware::class]);
    $r->put('/api/plants/{id}', [PlantController::class, 'update'], [AuthMiddleware::class]);
    $r->delete('/api/plants/{id}', [PlantController::class, 'destroy'], [AuthMiddleware::class]);
    $r->post('/api/plants/{id}/photo', [PlantController::class, 'uploadPhoto'], [AuthMiddleware::class]);
    $r->get('/api/plants/{id}/stats', [PlantController::class, 'plantStats'], [AuthMiddleware::class]);

    // Species
    $r->get('/api/species/search', [SpeciesController::class, 'search'], [AuthMiddleware::class]);
    $r->get('/api/species/{id}', [SpeciesController::class, 'show'], [AuthMiddleware::class]);
    $r->post('/api/species/import', [SpeciesController::class, 'import'], [AuthMiddleware::class]);

    // Tasks
    $r->get('/api/tasks', [TaskController::class, 'index'], [AuthMiddleware::class]);
    $r->get('/api/tasks/today', [TaskController::class, 'today'], [AuthMiddleware::class]);
    $r->get('/api/tasks/incoming', [TaskController::class, 'incoming'], [AuthMiddleware::class]);
    $r->get('/api/tasks/overdue', [TaskController::class, 'overdue'], [AuthMiddleware::class]);
    $r->get('/api/plants/{plantId}/tasks', [TaskController::class, 'plantTasks'], [AuthMiddleware::class]);
    $r->post('/api/plants/{plantId}/tasks', [TaskController::class, 'createForPlant'], [AuthMiddleware::class]);
    $r->post('/api/tasks', [TaskController::class, 'store'], [AuthMiddleware::class]);
    $r->patch('/api/tasks/{id}', [TaskController::class, 'update'], [AuthMiddleware::class]);
    $r->patch('/api/tasks/{id}/complete', [TaskController::class, 'complete'], [AuthMiddleware::class]);
    $r->patch('/api/tasks/{id}/skip', [TaskController::class, 'skip'], [AuthMiddleware::class]);
    $r->delete('/api/tasks/{id}', [TaskController::class, 'destroy'], [AuthMiddleware::class]);

    // History
    $r->get('/api/plants/{plantId}/history', [TaskController::class, 'plantHistory'], [AuthMiddleware::class]);
    $r->post('/api/plants/{plantId}/history', [TaskController::class, 'addPlantHistory'], [AuthMiddleware::class]);

    // Stats
    $r->get('/api/stats/overview', [StatsController::class, 'overview'], [AuthMiddleware::class]);

    // Notifications
    $r->get('/api/notifications', [NotificationController::class, 'index'], [AuthMiddleware::class]);
    $r->get('/api/notifications/today', [NotificationController::class, 'today'], [AuthMiddleware::class]);
    $r->get('/api/notifications/incoming', [NotificationController::class, 'incoming'], [AuthMiddleware::class]);
    $r->patch('/api/notifications/{id}/read', [NotificationController::class, 'markRead'], [AuthMiddleware::class]);
    $r->patch('/api/notifications/read-all', [NotificationController::class, 'markAllRead'], [AuthMiddleware::class]);

    // Admin
    $r->get('/api/admin/users', [AdminController::class, 'listUsers'], [AuthMiddleware::class, AdminMiddleware::class]);
    $r->patch('/api/admin/users/{id}/block', [AdminController::class, 'block'], [AuthMiddleware::class, AdminMiddleware::class]);
    $r->patch('/api/admin/users/{id}/unblock', [AdminController::class, 'unblock'], [AuthMiddleware::class, AdminMiddleware::class]);
    $r->delete('/api/admin/users/{id}', [AdminController::class, 'destroy'], [AuthMiddleware::class, AdminMiddleware::class]);
};
