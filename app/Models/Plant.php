<?php
namespace App\Models;

class Plant
{
    public int $id;
    public int $userId;
    public ?int $speciesId = null;
    public string $name = '';
    public ?string $customSpeciesName = null;
    public ?string $location = null;
    public ?string $plantedAt = null;
    public ?string $notes = null;
    public ?int $wateringIntervalDays = null;
    public ?int $fertilizingIntervalDays = null;
    public string $careLevel = 'easy';
    public bool $apiRecommendationsUsed = false;
    public string $healthStatus = 'healthy';

    public static function fromArray(array $data): self
    {
        $p = new self();
        $p->id = (int) ($data['id'] ?? 0);
        $p->userId = (int) ($data['user_id'] ?? 0);
        $p->speciesId = isset($data['species_id']) ? (int) $data['species_id'] : null;
        $p->name = $data['name'] ?? '';
        $p->customSpeciesName = $data['custom_species_name'] ?? null;
        $p->location = $data['location'] ?? null;
        $p->plantedAt = $data['planted_at'] ?? null;
        $p->notes = $data['notes'] ?? null;
        $p->wateringIntervalDays = $data['watering_interval_days'] ?? null;
        $p->fertilizingIntervalDays = $data['fertilizing_interval_days'] ?? null;
        $p->careLevel = $data['care_level'] ?? 'easy';
        $p->apiRecommendationsUsed = (bool) ($data['api_recommendations_used'] ?? false);
        $p->healthStatus = $data['health_status'] ?? 'healthy';
        return $p;
    }
}
