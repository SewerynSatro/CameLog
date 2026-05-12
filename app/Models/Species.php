<?php
namespace App\Models;

class Species
{
    public int $id;
    public ?string $externalApiId = null;
    public string $commonName = '';
    public ?string $scientificName = null;
    public ?string $careLevel = null;
    public ?string $wateringInfo = null;
    public ?string $sunlightInfo = null;
    public ?string $climateInfo = null;
    public ?array $rawApiData = null;
}
