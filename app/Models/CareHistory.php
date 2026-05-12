<?php
namespace App\Models;

class CareHistory
{
    public int $id;
    public int $userId;
    public int $plantId;
    public ?int $taskId = null;
    public string $type = 'watering';
    public ?string $note = null;
    public string $performedAt;
}
