<?php
/**
 * Główna konfiguracja aplikacji CameLog
 * Ładuje zmienne z pliku .env (jeśli istnieje) lub używa wartości domyślnych.
 */

if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // strip quotes
            $value = trim($value, "\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        return $value;
    }
}

// Załaduj plik .env (z roota projektu)
loadEnv(__DIR__ . '/../../.env');

return [
    'app' => [
        'name' => env('APP_NAME', 'CameLog'),
        'env' => env('APP_ENV', 'local'),
        'debug' => env('APP_DEBUG', true),
        'url' => env('APP_URL', 'http://localhost:8000'),
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'camelog'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
        'name' => 'camelog_session',
    ],
    'perenual' => [
        'api_key' => env('PERENUAL_API_KEY', ''),
        'base_url' => 'https://perenual.com/api',
    ],
    'upload' => [
        'max_size' => (int) env('UPLOAD_MAX_SIZE', 5 * 1024 * 1024),
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'plants_dir' => __DIR__ . '/../../public/uploads/plants',
    ],
];
