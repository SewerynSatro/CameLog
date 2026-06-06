<?php
namespace App\Services;

use App\Repositories\SpeciesRepository;

/**
 * Integracja z Perenual API. Klucz API jest tylko po stronie serwera.
 * Jeśli klucz nie jest ustawiony – używamy danych mockowych.
 */
class SpeciesApiService
{
    private const MIN_EXTERNAL_ID = 1;
    private const MAX_EXTERNAL_ID = 200000;

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

        $url = $this->baseUrl . '/v2/species-list?key=' . urlencode($this->apiKey) . '&q=' . urlencode($query);
        $resp = $this->httpGet($url);
        if ($resp === null) {
            // fallback mock
            return ['source' => 'mock-fallback', 'results' => $this->mockSearch($query)];
        }

        $data = json_decode($resp, true);
        $results = $this->mapSearchResults($data['data'] ?? []);
        return ['source' => 'perenual', 'results' => $results];
    }

    private function mapSearchResults(array $items): array
    {
        $results = [];
        foreach ($items as $item) {
            if (!$this->isAllowedExternalId((string) ($item['id'] ?? ''))) {
                continue;
            }

            $scientificName = is_array($item['scientific_name'] ?? null)
                ? implode(', ', $item['scientific_name'])
                : ($item['scientific_name'] ?? null);
            $result = [
                'external_id' => $item['id'] ?? null,
                'common_name' => $item['common_name'] ?? '-',
                'scientific_name' => $scientificName,
                'cycle' => $this->cleanApiValue($item['cycle'] ?? null),
                'watering' => $this->cleanApiValue($item['watering'] ?? null),
                'sunlight' => $this->cleanApiValue(is_array($item['sunlight'] ?? null) ? implode(', ', $item['sunlight']) : ($item['sunlight'] ?? null)),
                'thumbnail' => $item['default_image']['thumbnail'] ?? null,
            ];
            $results[] = $result;

            if (!empty($result['external_id']) && !empty($result['common_name'])) {
                $this->safeUpsert([
                    'external_api_id' => (string) $result['external_id'],
                    'common_name' => $result['common_name'],
                    'scientific_name' => $result['scientific_name'],
                    'care_level' => null,
                    'watering_info' => $result['watering'],
                    'sunlight_info' => $result['sunlight'],
                    'climate_info' => null,
                    'raw_api_data' => array_merge($item, ['external_id' => $result['external_id']]),
                ]);
            }
        }
        return $results;
    }

    private function legacyMapSearchResults(array $items): array
    {
        return [];
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
        if (!$this->isAllowedExternalId($externalId)) {
            return null;
        }

        $cached = $this->repo->findByExternalId($externalId);
        if ($cached && (!$this->hasKey() || !ctype_digit((string) $externalId))) {
            $raw = json_decode($cached['raw_api_data'] ?? 'null', true);
            return $this->mapDetail($raw ?: $cached, $cached['id']);
        }

        if (!$this->hasKey()) {
            $mock = $this->mockDetail($externalId);
            if ($mock === null) return null;
            $local = $this->safeUpsert([
                'external_api_id' => $externalId,
                'common_name' => $mock['common_name'],
                'scientific_name' => $mock['scientific_name'],
                'care_level' => $mock['care_level'],
                'watering_info' => $mock['watering_info'],
                'sunlight_info' => $mock['sunlight_info'],
                'climate_info' => $mock['climate_info'],
                'raw_api_data' => $mock,
            ]);
            if ($local !== null) {
                $mock['local_id'] = $local;
            }
            return $mock;
        }

        $url = $this->baseUrl . '/v2/species/details/' . urlencode($externalId) . '?key=' . urlencode($this->apiKey);
        $resp = $this->httpGet($url);
        if ($resp === null) {
            if ($cached) {
                $raw = json_decode($cached['raw_api_data'] ?? 'null', true);
                return $this->enrichWithFallbackRecommendations($this->mapDetail($raw ?: $cached, (int) $cached['id']));
            }
            return $this->mockDetail($externalId);
        }
        $data = json_decode($resp, true);
        if (!$data) return null;
        if (!empty($data['message']) && $this->isUpgradeText((string) $data['message'])) {
            if ($cached) {
                $raw = json_decode($cached['raw_api_data'] ?? 'null', true);
                return $this->enrichWithFallbackRecommendations($this->mapDetail($raw ?: $cached, (int) $cached['id']));
            }
            return $this->mockDetail($externalId);
        }

        $guide = $this->careGuide($externalId, $data['common_name'] ?? null);
        $wateringGuide = $guide['watering'] ?? null;
        $sunlightGuide = $guide['sunlight'] ?? null;
        $pruningGuide = $guide['pruning'] ?? null;
        $wateringIntervalDays = $this->wateringIntervalDays($data['watering_general_benchmark'] ?? null, $data['watering'] ?? null);
        $careLevel = $this->normalizeCareLevel($data['care_level'] ?? $data['maintenance'] ?? null);

        $mapped = $this->mapPerenualDetail($data, $externalId, $guide, $wateringIntervalDays, $careLevel);

        $localId = $this->safeUpsert([
            'external_api_id' => $mapped['external_id'],
            'common_name' => $mapped['common_name'],
            'scientific_name' => $mapped['scientific_name'],
            'care_level' => $mapped['care_level'],
            'watering_info' => $mapped['watering_info'],
            'sunlight_info' => $mapped['sunlight_info'],
            'climate_info' => $mapped['climate_info'],
            'raw_api_data' => array_merge($data, ['care_guide' => $guide]),
        ]);
        if ($localId !== null) {
            $mapped['local_id'] = $localId;
        } elseif ($cached) {
            $mapped['local_id'] = (int) $cached['id'];
            $mapped['cache_warning'] = 'Nie udało się odświeżyć lokalnego cache gatunku, ale dane z API zostały pobrane.';
        }
        return $this->enrichWithFallbackRecommendations($mapped);
    }

    public function importToLocal(array $species): int
    {
        $raw = $species;

        return $this->repo->upsert([
            'external_api_id' => $species['external_id'] ?? null,
            'common_name' => $species['common_name'] ?? '',
            'scientific_name' => $species['scientific_name'] ?? '',
            'care_level' => $species['care_level'] ?? null,
            'watering_info' => $species['watering_info'] ?? null,
            'sunlight_info' => $species['sunlight_info'] ?? null,
            'climate_info' => $species['climate_info'] ?? null,
            'raw_api_data' => $raw,
        ]);
    }

    private function mapDetail($raw, int $localId): array
    {
        $careGuide = is_array($raw['care_guide'] ?? null) ? $raw['care_guide'] : [];
        $wateringInfo = $raw['watering_info'] ?? $careGuide['watering'] ?? $raw['watering'] ?? null;
        $sunlightInfo = $raw['sunlight_info'] ?? $careGuide['sunlight'] ?? $raw['sunlight'] ?? null;
        $pruningGuide = $careGuide['pruning'] ?? $raw['pruning_guide'] ?? null;
        if (is_array($sunlightInfo)) $sunlightInfo = implode(', ', $sunlightInfo);
        $wateringInfo = $this->cleanApiValue($wateringInfo);
        $sunlightInfo = $this->cleanApiValue($sunlightInfo);

        $careLevel = $this->normalizeCareLevel($raw['care_level'] ?? $raw['maintenance'] ?? null);
        $image = $raw['default_image'] ?? $raw['image'] ?? null;

        return [
            'local_id' => $localId,
            'external_id' => $raw['external_api_id'] ?? $raw['external_id'] ?? $raw['id'] ?? null,
            'common_name' => $raw['common_name'] ?? '',
            'scientific_name' => is_array($raw['scientific_name'] ?? null) ? implode(', ', $raw['scientific_name']) : ($raw['scientific_name'] ?? ''),
            'other_name' => $this->cleanList($raw['other_name'] ?? []),
            'family' => $this->cleanApiValue($raw['family'] ?? null),
            'genus' => $this->cleanApiValue($raw['genus'] ?? null),
            'origin' => $this->cleanList($raw['origin'] ?? []),
            'type' => $this->cleanApiValue($raw['type'] ?? null),
            'cycle' => $this->cleanApiValue($raw['cycle'] ?? null),
            'dimensions' => $this->formatDimensions($raw['dimensions'] ?? []),
            'hardiness' => $this->formatHardiness($raw['hardiness'] ?? null),
            'care_level' => $careLevel,
            'maintenance' => $this->cleanApiValue($raw['maintenance'] ?? null),
            'watering_info' => $wateringInfo,
            'watering_general_benchmark' => $raw['watering_general_benchmark'] ?? null,
            'watering_benchmark_label' => $this->formatWateringBenchmark($raw['watering_general_benchmark'] ?? null),
            'watering_interval_days' => $this->wateringIntervalDays($raw['watering_general_benchmark'] ?? null, $raw['watering'] ?? $wateringInfo),
            'fertilizing_interval_days' => $this->fertilizingIntervalDays($careLevel),
            'sunlight_info' => $sunlightInfo,
            'climate_info' => $raw['climate_info'] ?? $this->formatHardiness($raw['hardiness'] ?? null),
            'growth_rate' => $this->cleanApiValue($raw['growth_rate'] ?? null),
            'pruning_month' => $this->cleanList($raw['pruning_month'] ?? []),
            'propagation' => $this->cleanList($raw['propagation'] ?? []),
            'attracts' => $this->cleanList($raw['attracts'] ?? []),
            'soil' => $this->cleanList($raw['soil'] ?? []),
            'description' => $this->joinDescriptions([
                $raw['description'] ?? null,
                !empty($careGuide['watering']) ? 'Podlewanie: ' . $careGuide['watering'] : null,
                !empty($careGuide['sunlight']) ? 'Światło: ' . $careGuide['sunlight'] : null,
                !empty($pruningGuide) ? 'Przycinanie: ' . $pruningGuide : null,
            ]),
            'image' => is_array($image) ? [
                'thumbnail' => $image['thumbnail'] ?? null,
                'small_url' => $image['small_url'] ?? null,
                'medium_url' => $image['medium_url'] ?? null,
                'regular_url' => $image['regular_url'] ?? null,
                'original_url' => $image['original_url'] ?? null,
            ] : null,
            'image_url' => is_array($image) ? ($image['small_url'] ?? $image['medium_url'] ?? $image['regular_url'] ?? $image['thumbnail'] ?? null) : null,
            'drought_tolerant' => $this->cleanBool($raw['drought_tolerant'] ?? null),
            'salt_tolerant' => $this->cleanBool($raw['salt_tolerant'] ?? null),
            'thorny' => $this->cleanBool($raw['thorny'] ?? null),
            'invasive' => $this->cleanBool($raw['invasive'] ?? null),
            'tropical' => $this->cleanBool($raw['tropical'] ?? null),
            'indoor' => $this->cleanBool($raw['indoor'] ?? null),
            'flowers' => $this->cleanBool($raw['flowers'] ?? null),
            'flowering_season' => $this->cleanApiValue($raw['flowering_season'] ?? null),
            'fruits' => $this->cleanBool($raw['fruits'] ?? null),
            'edible_fruit' => $this->cleanBool($raw['edible_fruit'] ?? null),
            'harvest_season' => $this->cleanApiValue($raw['harvest_season'] ?? null),
            'leaf' => $this->cleanBool($raw['leaf'] ?? null),
            'edible_leaf' => $this->cleanBool($raw['edible_leaf'] ?? null),
            'medicinal' => $this->cleanBool($raw['medicinal'] ?? null),
            'poisonous_to_humans' => $this->cleanBool($raw['poisonous_to_humans'] ?? null),
            'poisonous_to_pets' => $this->cleanBool($raw['poisonous_to_pets'] ?? null),
            'care_guide' => $careGuide,
        ];
    }

    private function mapPerenualDetail(array $data, string $externalId, array $guide, ?int $wateringIntervalDays, ?string $careLevel): array
    {
        $wateringGuide = $guide['watering'] ?? null;
        $sunlightGuide = $guide['sunlight'] ?? null;
        $pruningGuide = $guide['pruning'] ?? null;
        $image = $data['default_image'] ?? null;

        return [
            'external_id' => $data['id'] ?? $externalId,
            'common_name' => $data['common_name'] ?? '',
            'scientific_name' => is_array($data['scientific_name'] ?? null) ? implode(', ', $data['scientific_name']) : ($data['scientific_name'] ?? ''),
            'other_name' => $this->cleanList($data['other_name'] ?? []),
            'family' => $this->cleanApiValue($data['family'] ?? null),
            'genus' => $this->cleanApiValue($data['genus'] ?? null),
            'origin' => $this->cleanList($data['origin'] ?? []),
            'type' => $this->cleanApiValue($data['type'] ?? null),
            'cycle' => $this->cleanApiValue($data['cycle'] ?? null),
            'dimensions' => $this->formatDimensions($data['dimensions'] ?? []),
            'hardiness' => $this->formatHardiness($data['hardiness'] ?? null),
            'care_level' => $careLevel,
            'maintenance' => $this->cleanApiValue($data['maintenance'] ?? null),
            'watering_info' => $wateringGuide ?: $this->cleanApiValue($data['watering'] ?? null),
            'watering_general_benchmark' => $data['watering_general_benchmark'] ?? null,
            'watering_benchmark_label' => $this->formatWateringBenchmark($data['watering_general_benchmark'] ?? null),
            'watering_interval_days' => $wateringIntervalDays,
            'fertilizing_interval_days' => $this->fertilizingIntervalDays($careLevel),
            'sunlight_info' => $this->cleanApiValue(is_array($data['sunlight'] ?? null) ? implode(', ', $data['sunlight']) : ($data['sunlight'] ?? null)),
            'climate_info' => $this->formatHardiness($data['hardiness'] ?? null),
            'growth_rate' => $this->cleanApiValue($data['growth_rate'] ?? null),
            'pruning_month' => $this->cleanList($data['pruning_month'] ?? []),
            'propagation' => $this->cleanList($data['propagation'] ?? []),
            'attracts' => $this->cleanList($data['attracts'] ?? []),
            'soil' => $this->cleanList($data['soil'] ?? []),
            'description' => $this->joinDescriptions([
                $data['description'] ?? null,
                $wateringGuide ? 'Podlewanie: ' . $wateringGuide : null,
                $sunlightGuide ? 'Światło: ' . $sunlightGuide : null,
                $pruningGuide ? 'Przycinanie: ' . $pruningGuide : null,
            ]),
            'image' => is_array($image) ? [
                'thumbnail' => $image['thumbnail'] ?? null,
                'small_url' => $image['small_url'] ?? null,
                'medium_url' => $image['medium_url'] ?? null,
                'regular_url' => $image['regular_url'] ?? null,
                'original_url' => $image['original_url'] ?? null,
            ] : null,
            'image_url' => is_array($image) ? ($image['small_url'] ?? $image['medium_url'] ?? $image['regular_url'] ?? $image['thumbnail'] ?? null) : null,
            'drought_tolerant' => $this->cleanBool($data['drought_tolerant'] ?? null),
            'salt_tolerant' => $this->cleanBool($data['salt_tolerant'] ?? null),
            'thorny' => $this->cleanBool($data['thorny'] ?? null),
            'invasive' => $this->cleanBool($data['invasive'] ?? null),
            'tropical' => $this->cleanBool($data['tropical'] ?? null),
            'indoor' => $this->cleanBool($data['indoor'] ?? null),
            'flowers' => $this->cleanBool($data['flowers'] ?? null),
            'flowering_season' => $this->cleanApiValue($data['flowering_season'] ?? null),
            'fruits' => $this->cleanBool($data['fruits'] ?? null),
            'edible_fruit' => $this->cleanBool($data['edible_fruit'] ?? null),
            'harvest_season' => $this->cleanApiValue($data['harvest_season'] ?? null),
            'leaf' => $this->cleanBool($data['leaf'] ?? null),
            'edible_leaf' => $this->cleanBool($data['edible_leaf'] ?? null),
            'medicinal' => $this->cleanBool($data['medicinal'] ?? null),
            'poisonous_to_humans' => $this->cleanBool($data['poisonous_to_humans'] ?? null),
            'poisonous_to_pets' => $this->cleanBool($data['poisonous_to_pets'] ?? null),
            'care_guide' => $guide,
            'sunlight_guide' => $sunlightGuide,
            'pruning_guide' => $pruningGuide,
        ];
    }

    private function careGuide(string $externalId, ?string $commonName = null): array
    {
        if (!$this->isAllowedExternalId($externalId)) {
            return [];
        }

        $guide = [];
        $url = $this->baseUrl . '/species-care-guide-list?key=' . urlencode($this->apiKey) . '&species_id=' . urlencode($externalId);
        $resp = $this->httpGet($url);

        if ($resp === null && $commonName) {
            $url = $this->baseUrl . '/species-care-guide-list?key=' . urlencode($this->apiKey) . '&q=' . urlencode($commonName);
            $resp = $this->httpGet($url);
        }
        if ($resp === null) return $guide;

        $data = json_decode($resp, true);
        if (!is_array($data)) return $guide;

        $items = $data['data'] ?? [];
        if (!is_array($items) || !array_is_list($items) || empty($items)) return $guide;

        $first = $items[0] ?? null;
        if (!is_array($first)) return $guide;

        $sections = $first['section'] ?? [];
        if (!is_array($sections)) return $guide;

        foreach ($sections as $section) {
            if (!is_array($section) || empty($section['type']) || empty($section['description'])) continue;
            $guide[(string) $section['type']] = (string) $section['description'];
        }
        return $guide;
    }

    private function wateringIntervalDays($benchmark, ?string $watering): ?int
    {
        if (is_array($benchmark) && !empty($benchmark['value'])) {
            if (is_numeric($benchmark['value'])) return (int) $benchmark['value'];
            if (preg_match_all('/\d+/', (string) $benchmark['value'], $m) && !empty($m[0])) {
                $nums = array_map('intval', $m[0]);
                return (int) round(array_sum($nums) / count($nums));
            }
        }

        return match (strtolower((string) $watering)) {
            'frequent' => 4,
            'average' => 7,
            'minimum' => 14,
            'none' => 30,
            default => null,
        };
    }

    private function fertilizingIntervalDays(?string $careLevel): int
    {
        return match ($this->normalizeCareLevel($careLevel)) {
            'hard' => 21,
            'medium' => 30,
            default => 45,
        };
    }

    private function normalizeCareLevel(?string $value): ?string
    {
        $v = strtolower(trim((string) $value));
        return match ($v) {
            'low', 'easy' => 'easy',
            'medium', 'moderate' => 'medium',
            'high', 'hard', 'difficult' => 'hard',
            default => $v !== '' ? $v : null,
        };
    }

    private function joinDescriptions(array $parts): ?string
    {
        $parts = array_values(array_filter(array_map(fn($p) => is_string($p) ? trim($p) : null, $parts)));
        return $parts ? implode("\n\n", $parts) : null;
    }

    private function enrichWithFallbackRecommendations(array $detail): array
    {
        $hasCare = !empty($detail['watering_info']) || !empty($detail['sunlight_info']) || !empty($detail['description']);
        if ($hasCare) return $detail;

        $mock = $this->findMockByName($detail['common_name'] ?? '', $detail['scientific_name'] ?? '');
        if (!$mock) return $detail;

        $externalId = $detail['external_id'] ?? null;
        $localId = $detail['local_id'] ?? null;
        $detail = array_merge($detail, $mock);
        if ($externalId) $detail['external_id'] = $externalId;
        if ($localId) $detail['local_id'] = $localId;
        $detail['recommendation_source'] = 'mock-fallback';
        return $detail;
    }

    private function findMockByName(string $commonName, string $scientificName): ?array
    {
        $haystack = mb_strtolower($commonName . ' ' . $scientificName);
        foreach ($this->mockData() as $mock) {
            $common = mb_strtolower($mock['common_name']);
            $scientific = mb_strtolower($mock['scientific_name']);
            if (($common && str_contains($haystack, $common)) || ($scientific && str_contains($haystack, $scientific))) {
                return $mock;
            }
            $genus = strtok($scientific, ' ');
            if ($genus && str_contains($haystack, $genus)) {
                return $mock;
            }
        }
        return null;
    }

    private function safeUpsert(array $data): ?int
    {
        try {
            return $this->repo->upsert($data);
        } catch (\Throwable $e) {
            error_log('[CameLog] Species cache upsert failed: ' . $e->getMessage());
            return null;
        }
    }

    private function cleanList($value): array
    {
        if (!is_array($value)) {
            $value = $value === null || $value === '' ? [] : [$value];
        }

        $out = [];
        foreach ($value as $item) {
            $clean = $this->cleanApiValue($item);
            if ($clean !== null) $out[] = $clean;
        }
        return array_values(array_unique($out));
    }

    private function cleanBool($value): ?bool
    {
        if (is_bool($value)) return $value;
        if ($value === null || $this->isUpgradeText((string) $value)) return null;
        if (is_numeric($value)) return (bool) $value;
        $v = strtolower(trim((string) $value));
        if (in_array($v, ['true', 'yes', '1'], true)) return true;
        if (in_array($v, ['false', 'no', '0'], true)) return false;
        return null;
    }

    private function formatHardiness($hardiness): ?string
    {
        if (!is_array($hardiness)) return $this->cleanApiValue($hardiness);
        $min = $this->cleanApiValue($hardiness['min'] ?? null);
        $max = $this->cleanApiValue($hardiness['max'] ?? null);
        if ($min && $max) return 'Strefy ' . $min . '–' . $max;
        if ($min) return 'Od strefy ' . $min;
        if ($max) return 'Do strefy ' . $max;
        return null;
    }

    private function formatDimensions($dimensions): ?string
    {
        if (!is_array($dimensions)) return $this->cleanApiValue($dimensions);
        $items = array_is_list($dimensions) ? $dimensions : [$dimensions];
        $formatted = [];

        foreach ($items as $dimension) {
            if (!is_array($dimension)) continue;
            $type = $this->cleanApiValue($dimension['type'] ?? null);
            $min = $dimension['min_value'] ?? null;
            $max = $dimension['max_value'] ?? null;
            $unit = $this->cleanApiValue($dimension['unit'] ?? null);

            $range = null;
            if ($min !== null && $max !== null) {
                $range = ((string) $min) . '–' . ((string) $max);
            } elseif ($min !== null) {
                $range = 'od ' . ((string) $min);
            } elseif ($max !== null) {
                $range = 'do ' . ((string) $max);
            }

            if ($range) {
                $formatted[] = trim(($type ? $type . ': ' : '') . $range . ($unit ? ' ' . $unit : ''));
            }
        }

        return $formatted ? implode(', ', $formatted) : null;
    }

    private function formatWateringBenchmark($benchmark): ?string
    {
        if (!is_array($benchmark)) return $this->cleanApiValue($benchmark);
        $value = $this->cleanApiValue($benchmark['value'] ?? null);
        $unit = $this->cleanApiValue($benchmark['unit'] ?? null);
        if (!$value) return null;
        $value = trim($value, " \t\n\r\0\x0B\"");
        return $unit ? $value . ' ' . $unit : $value;
    }

    private function cleanApiValue($value): ?string
    {
        if (is_array($value)) $value = implode(', ', $value);
        if ($value === null) return null;
        $value = trim((string) $value);
        if ($value === '' || $this->isUpgradeText($value)) return null;
        return $value;
    }

    private function isUpgradeText(string $value): bool
    {
        $v = mb_strtolower($value);
        return str_contains($v, 'upgrade plan') || str_contains($v, 'upgrade access') || str_contains($v, 'subscription-api-pricing');
    }

    private function isAllowedExternalId(string $externalId): bool
    {
        if (!ctype_digit($externalId)) {
            return str_starts_with($externalId, 'mock-');
        }

        $id = (int) $externalId;
        return $id >= self::MIN_EXTERNAL_ID && $id <= self::MAX_EXTERNAL_ID;
    }

    private function httpGet(string $url): ?string
    {
        $headers = [
            'User-Agent: CameLog/1.0',
            'Accept: application/json',
        ];

        if (!function_exists('curl_init')) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'ignore_errors' => true,
                    'header' => implode("\r\n", $headers) . "\r\n",
                ],
            ]);
            $r = @file_get_contents($url, false, $ctx);
            if ($r === false) return null;

            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('/\s(\d{3})\s/', $statusLine, $m) && (int) $m[1] >= 400) {
                return null;
            }
            return $r;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
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
