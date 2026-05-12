<?php
namespace App\Core;

/**
 * Hermetyzuje dostęp do danych żądania HTTP.
 */
class Request
{
    private array $params = [];
    private array $body = [];
    private array $query;
    private array $files;
    private string $method;
    private string $path;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->resolvePath();
        $this->query = $_GET ?? [];
        $this->files = $_FILES ?? [];
        $this->body = $this->parseBody();
    }

    private function resolvePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        // Odetnij /index.php jeśli jest w URL
        $uri = preg_replace('#^/index\.php#', '', $uri);
        if ($uri === '' || $uri === false) $uri = '/';
        return rtrim($uri, '/') ?: '/';
    }

    private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $_POST ?? [];
    }

    public function method(): string { return strtoupper($this->method); }
    public function path(): string { return $this->path; }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function setParams(array $params): void { $this->params = $params; }
    public function param(string $key, $default = null) { return $this->params[$key] ?? $default; }
}
