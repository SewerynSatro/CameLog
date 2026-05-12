<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\SpeciesApiService;
use App\Repositories\SpeciesRepository;

class SpeciesController
{
    private SpeciesApiService $api;
    private SpeciesRepository $repo;

    public function __construct()
    {
        $this->api = new SpeciesApiService();
        $this->repo = new SpeciesRepository();
    }

    public function search(Request $req): void
    {
        $q = trim((string) $req->query('query', ''));
        if ($q === '') Response::json(['source' => 'empty', 'results' => []]);
        $r = $this->api->search($q);
        Response::json($r);
    }

    public function show(Request $req): void
    {
        $id = (string) $req->param('id');
        // Najpierw sprawdź lokalną bazę po liczbowym ID
        if (ctype_digit($id)) {
            $local = $this->repo->findById((int) $id);
            if ($local) {
                $detail = $this->api->detail($local['external_api_id'] ?? $id) ?? [
                    'local_id' => $local['id'],
                    'common_name' => $local['common_name'],
                    'scientific_name' => $local['scientific_name'],
                    'care_level' => $local['care_level'],
                    'watering_info' => $local['watering_info'],
                    'sunlight_info' => $local['sunlight_info'],
                    'climate_info' => $local['climate_info'],
                ];
                Response::json(['species' => $detail]);
            }
        }
        $detail = $this->api->detail($id);
        if (!$detail) Response::notFound('Gatunek nie znaleziony');
        Response::json(['species' => $detail]);
    }

    public function import(Request $req): void
    {
        $data = $req->all();
        if (empty($data['common_name'])) Response::error('Brak nazwy', 422);
        $id = $this->api->importToLocal($data);
        Response::json(['species_id' => $id]);
    }
}
