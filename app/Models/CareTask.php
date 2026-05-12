<?php
namespace App\Models;

class CareTask
{
    public int $id;
    public int $userId;
    public int $plantId;
    public string $type = 'watering';
    public string $title = '';
    public ?string $description = null;
    public string $dueDate;
    public string $status = 'pending';
    public ?int $repeatIntervalDays = null;
    public string $priority = 'normal';
    public ?string $completedAt = null;
}
