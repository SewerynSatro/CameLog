<?php
namespace App\Core;

/**
 * Pomocnicza klasa do zwracania odpowiedzi HTTP/JSON.
 */
class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        self::json(array_merge(['error' => $message], $extra), $status);
    }

    public static function notFound(string $message = 'Nie znaleziono zasobu'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Wymagana autoryzacja'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Brak uprawnień'): void
    {
        self::error($message, 403);
    }
}
