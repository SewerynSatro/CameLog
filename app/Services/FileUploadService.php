<?php
namespace App\Services;

class FileUploadService
{
    private array $config;

    public function __construct()
    {
        $cfg = require __DIR__ . '/../config/config.php';
        $this->config = $cfg['upload'];
    }

    /**
     * Zwraca względną ścieżkę pliku w stosunku do public/.
     */
    public function uploadPlantPhoto(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['ok' => false, 'message' => 'Nieprawidłowy upload.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Błąd uploadu (kod: ' . $file['error'] . ').'];
        }
        if ($file['size'] > $this->config['max_size']) {
            return ['ok' => false, 'message' => 'Plik jest za duży.'];
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->config['allowed_types'], true)) {
            return ['ok' => false, 'message' => 'Niedozwolony typ pliku.'];
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
        $filename = bin2hex(random_bytes(12)) . '.' . $ext;
        $dir = $this->config['plants_dir'];
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $target = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['ok' => false, 'message' => 'Nie udało się zapisać pliku.'];
        }
        return ['ok' => true, 'path' => 'uploads/plants/' . $filename];
    }
}
