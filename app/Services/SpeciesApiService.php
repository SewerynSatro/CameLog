<?php
namespace App\Services;

use App\Repositories\SpeciesRepository;

/**
 * Integracja z Perenual API. Klucz API jest tylko po stronie serwera.
 * Jeśli klucz nie jest ustawiony – używamy danych mockowych.
 */
class SpeciesApiService
{
    private string $apiKey;
    private string $baseUrl;
    private SpeciesRepository $repo;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->apiKey = $config['perenual']['api_key'] ?? '';
        $this->baseUrl = $config['perenual']['base_url'] ?? 'https://perenual.com/api';
        $this->repo = new SpeciesRepository();
    }

    public function hasKey(): bool
    {
        return !empty($this->apiKey);
    }

    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') return ['source' => 'empty', 'results' => []];

        if (!$this->hasKey()) {
            return ['source' => 'mock', 'results' => $this->mockSearch($query)];
        }

        $url = $this->baseUrl . '/species-list?key=' . urlencode($this->apiKey) . '&q=' . urlencode($query);
        $resp = $this->httpGet($url);
        if ($resp === null) {
            // fallback mock
            return ['source' => 'mock-fallback', 'results' => $this->mockSearch($query)];
        }

        $data = json_decode($resp, true);
        $results = [];
        foreach (($data['data'] ?? []) as $item) {
            $results[] = [
                'external_id' => $item['id'] ?? null,
                'common_name' => $item['common_name'] ?? '—',
                'scientific_name' => is_array($item['scientific_name'] ?? null) ? implode(', ', $item['scientific_name']) : ($item['scientific_name'] ?? null),
                'cycle' => $item['cycle'] ?? null,
                'watering' => $item['watering'] ?? null,
                'sunlight' => is_array($item['sunlight'] ?? null) ? implode(', ', $item['sunlight']) : ($item['sunlight'] ?? null),
                'thumbnail' => $item['default_image']['thumbnail'] ?? null,
            ];
        }
        return ['source' => 'perenual', 'results' => $results];
    }

    public function detail(string $externalId): ?array
    {
        $cached = $this->repo->findByExternalId($externalId);
        if ($cached) {
            $raw = json_decode($cached['raw_api_data'] ?? 'null', true);
            return $this->mapDetail($raw ?: $cached, $cached['id']);
        }

        if (!$this->hasKey()) {
            $mock = $this->mockDetail($externalId);
            if ($mock === null) return null;
            $local = $this->repo->upsert([
                'external_api_id' => $externalId,
                'common_name' => $mock['common_name'],
                'scientific_name' => $mock['scientific_name'],
                'care_level' => $mock['care_level'],
                'watering_info' => $mock['watering_info'],
                'sunlight_info' => $mock['sunlight_info'],
                'climate_info' => $mock['climate_info'],
                'raw_api_data' => $mock,
            ]);
            $mock['local_id'] = $local;
            return $mock;
        }

        $url = $this->baseUrl . '/species/details/' . urlencode($externalId) . '?key=' . urlencode($this->apiKey);
        $resp = $this->httpGet($url);
        if ($resp === null) return $this->mockDetail($externalId);
        $data = json_decode($resp, true);
        if (!$data) return null;

        $mapped = [
            'external_id' => $data['id'] ?? $externalId,
            'common_name' => $data['common_name'] ?? '',
            'scientific_name' => is_array($data['scientific_name'] ?? null) ? implode(', ', $data['scientific_name']) : ($data['scientific_name'] ?? ''),
            'care_level' => $data['care_level'] ?? null,
            'watering_info' => $data['watering'] ?? null,
            'sunlight_info' => is_array($data['sunlight'] ?? null) ? implode(', ', $data['sunlight']) : ($data['sunlight'] ?? null),
            'climate_info' => $data['hardiness']['min'] ?? null,
            'cycle' => $data['cycle'] ?? null,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? null,
            'watering_general_benchmark' => $data['watering_general_benchmark'] ?? null,
        ];

        $localId = $this->repo->upsert([
            'external_api_id' => $mapped['external_id'],
            'common_name' => $mapped['common_name'],
            'scientific_name' => $mapped['scientific_name'],
            'care_level' => $mapped['care_level'],
            'watering_info' => $mapped['watering_info'],
            'sunlight_info' => $mapped['sunlight_info'],
            'climate_info' => $mapped['climate_info'],
            'raw_api_data' => $data,
        ]);
        $mapped['local_id'] = $localId;
        return $mapped;
    }

    public function importToLocal(array $species): int
    {
        return $this->repo->upsert([
            'external_api_id' => $species['external_id'] ?? null,
            'common_name' => $species['common_name'] ?? '',
            'scientific_name' => $species['scientific_name'] ?? '',
            'care_level' => $species['care_level'] ?? null,
            'watering_info' => $species['watering_info'] ?? null,
            'sunlight_info' => $species['sunlight_info'] ?? null,
            'climate_info' => $species['climate_info'] ?? null,
            'raw_api_data' => $species,
        ]);
    }

    private function mapDetail($raw, int $localId): array
    {
        return [
            'local_id' => $localId,
            'external_id' => $raw['external_api_id'] ?? $raw['external_id'] ?? null,
            'common_name' => $raw['common_name'] ?? '',
            'scientific_name' => $raw['scientific_name'] ?? '',
            'care_level' => $raw['care_level'] ?? null,
            'watering_info' => $raw['watering_info'] ?? $raw['watering'] ?? null,
            'sunlight_info' => $raw['sunlight_info'] ?? $raw['sunlight'] ?? null,
            'climate_info' => $raw['climate_info'] ?? null,
            'cycle' => $raw['cycle'] ?? null,
            'description' => $raw['description'] ?? null,
            'type' => $raw['type'] ?? null,
        ];
    }

    private function httpGet(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
            $r = @file_get_contents($url, false, $ctx);
            return $r === false ? null : $r;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $r = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($r === false || $code >= 400) return null;
        return $r;
    }

    /**
     * Mock data – używane gdy nie ma klucza Perenual.
     */
    private function mockData(): array
    {
        return [
            [
                'external_id' => 'mock-monstera',
                'common_name' => 'Monstera dziurawa',
                'scientific_name' => 'Monstera deliciosa',
                'care_level' => 'easy',
                'watering_info' => 'Average',
                'sunlight_info' => 'Jasne, rozproszone światło',
                'climate_info' => 'Tropikalny',
                'cycle' => 'Perennial',
                'description' => 'Roślina tropikalna, preferuje podwyższoną wilgotność powietrza. Należy unikać bezpośredniego, ostrego słońca, które może poparzyć liście. Podłoże powinno lekko przeschnąć przed kolejnym obfitym podlaniem.',
                'type' => 'Pnącze / Doniczkowa',
                'watering_interval_days' => 7,
                'fertilizing_interval_days' => 30,
            ],
            [
                'external_id' => 'mock-ficus',
                'common_name' => 'Fikus sprężysty',
                'scientific_name' => 'Ficus elastica',
                'care_level' => 'easy',
                'watering_info' => 'Average',
                'sunlight_info' => 'Jasne, rozproszone',
                'climate_info' => 'Umiarkowany',
                'cycle' => 'Perennial',
                'description' => 'Toleruje rzadsze podlewanie. Lubi miejsce z dużą ilością światła, ale nie znosi bezpośredniego słońca. Regularnie przecieraj liście z kurzu.',
                'type' => 'Drzewko doniczkowe',
                'watering_interval_days' => 10,
                'fertilizing_interval_days' => 30,
            ],
            [
                'external_id' => 'mock-calathea',
                'common_name' => 'Kalatea Orbifolia',
                'scientific_name' => 'Calathea orbifolia',
                'care_level' => 'medium',
                'watering_info' => 'Frequent',
                'sunlight_info' => 'Półcień',
                'climate_info' => 'Tropikalny wilgotny',
                'cycle' => 'Perennial',
                'description' => 'Wymaga wysokiej wilgotności powietrza. Nie znosi przesuszenia ani twardej wody. Najlepiej rośnie w cieniu, z dala od bezpośredniego słońca.',
                'type' => 'Doniczkowa',
                'watering_interval_days' => 4,
                'fertilizing_interval_days' => 21,
            ],
            [
                'external_id' => 'mock-zz',
                'common_name' => 'Zamiokulkas',
                'scientific_name' => 'Zamioculcas zamiifolia',
                'care_level' => 'easy',
                'watering_info' => 'Minimum',
                'sunlight_info' => 'Cień / półcień',
                'climate_info' => 'Suchy',
                'cycle' => 'Perennial',
                'description' => 'Bardzo wytrzymała roślina dla początkujących. Toleruje cień i rzadkie podlewanie. Nie przelewaj – łatwo gnije.',
                'type' => 'Sukulent / Doniczkowa',
                'watering_interval_days' => 14,
                'fertilizing_interval_days' => 60,
            ],
            [
                'external_id' => 'mock-pilea',
                'common_name' => 'Pilea peperomiowata',
                'scientific_name' => 'Pilea peperomioides',
                'care_level' => 'easy',
                'watering_info' => 'Average',
                'sunlight_info' => 'Jasne, rozproszone',
                'climate_info' => 'Umiarkowany',
                'cycle' => 'Perennial',
                'description' => 'Roślina kompaktowa, wytwarzająca liczne młode pędy. Lubi obracać się w stronę światła – obracaj doniczkę co kilka dni.',
                'type' => 'Doniczkowa',
                'watering_interval_days' => 7,
                'fertilizing_interval_days' => 30,
            ],
            [
                'external_id' => 'mock-aloes',
                'common_name' => 'Aloes zwyczajny',
                'scientific_name' => 'Aloe vera',
                'care_level' => 'easy',
                'watering_info' => 'Minimum',
                'sunlight_info' => 'Pełne słońce',
                'climate_info' => 'Suchy / pustynny',
                'cycle' => 'Perennial',
                'description' => 'Sukulent, lubi pełne słońce. Podlewaj rzadko, gdy podłoże całkowicie wyschnie.',
                'type' => 'Sukulent',
                'watering_interval_days' => 14,
                'fertilizing_interval_days' => 60,
            ],
            [
                'external_id' => 'mock-sansewieria',
                'common_name' => 'Sansewieria',
                'scientific_name' => 'Sansevieria trifasciata',
                'care_level' => 'easy',
                'watering_info' => 'Minimum',
                'sunlight_info' => 'Toleruje cień i słońce',
                'climate_info' => 'Suchy',
                'cycle' => 'Perennial',
                'description' => 'Niemal niezniszczalna. Idealna dla początkujących. Lubi rzadkie podlewanie, oczyszcza powietrze.',
                'type' => 'Doniczkowa',
                'watering_interval_days' => 14,
                'fertilizing_interval_days' => 60,
            ],
        ];
    }

    private function mockSearch(string $query): array
    {
        $q = mb_strtolower($query);
        $out = [];
        foreach ($this->mockData() as $item) {
            if (str_contains(mb_strtolower($item['common_name']), $q) ||
                str_contains(mb_strtolower($item['scientific_name']), $q)) {
                $out[] = [
                    'external_id' => $item['external_id'],
                    'common_name' => $item['common_name'],
                    'scientific_name' => $item['scientific_name'],
                    'cycle' => $item['cycle'],
                    'watering' => $item['watering_info'],
                    'sunlight' => $item['sunlight_info'],
                    'thumbnail' => null,
                ];
            }
        }
        // Jeśli brak dopasowań - zwróć max 3 rośliny demo
        if (empty($out)) {
            foreach (array_slice($this->mockData(), 0, 3) as $item) {
                $out[] = [
                    'external_id' => $item['external_id'],
                    'common_name' => $item['common_name'],
                    'scientific_name' => $item['scientific_name'],
                    'cycle' => $item['cycle'],
                    'watering' => $item['watering_info'],
                    'sunlight' => $item['sunlight_info'],
                    'thumbnail' => null,
                ];
            }
        }
        return $out;
    }

    private function mockDetail(string $externalId): ?array
    {
        foreach ($this->mockData() as $item) {
            if ($item['external_id'] === $externalId) return $item;
        }
        return null;
    }
}
