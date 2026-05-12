<?php
/**
 * CameLog - Front Controller
 * Obsługuje:
 *   - żądania API (/api/*) – delegowane do REST routera,
 *   - serwowanie SPA (każda inna ścieżka -> index.html).
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

// Autoloader PSR-4 dla namespace App\
spl_autoload_register(function (string $class) {
    if (!str_starts_with($class, 'App\\')) return;
    $rel = str_replace('\\', '/', substr($class, 4));
    $file = __DIR__ . '/../app/' . $rel . '.php';
    if (is_file($file)) require $file;
});

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

// CORS (na potrzeby dev np. uruchomienia frontu na innym porcie)
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

Auth::start();

$request = new Request();
$path = $request->path();

// Ścieżka /api/* -> REST router
if (str_starts_with($path, '/api')) {
    try {
        $router = new Router();
        $registerRoutes = require __DIR__ . '/../app/config/routes.php';
        $registerRoutes($router);
        $router->dispatch($request);
    } catch (Throwable $e) {
        $cfg = require __DIR__ . '/../app/config/config.php';
        $msg = ($cfg['app']['debug'] ?? false)
            ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
            : 'Błąd serwera';
        Response::error($msg, 500);
    }
    exit;
}

// Każda inna ścieżka -> SPA (frontend)
$indexFile = __DIR__ . '/index.html';
if (is_file($indexFile)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($indexFile);
    exit;
}

http_response_code(404);
echo 'Not found';
