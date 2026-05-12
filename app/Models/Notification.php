<?php
namespace App\Models;

class Notification
{
    public int $id;
    public int $userId;
    public ?int $taskId = null;
    public string $title = '';
    public ?string $message = null;
    public string $type = 'system';
    public bool $isRead = false;
    public string $createdAt;
}
