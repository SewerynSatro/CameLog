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

        // Ten endpoint jest używany z frontendu głównie jako ID z Perenual.
        // Najpierw pobieramy szczegóły po external_api_id, aby lokalne ID rekordu
        // nie zasłoniło gatunku z API o tym samym numerze.
        $detail = $this->api->detail($id);
        if ($detail) {
            Response::json(['species' => $detail]);
        }

        // Fallback dla ręcznego odczytu lokalnego rekordu gatunku.
        if (ctype_digit($id)) {
            $local = $this->repo->findById((int) $id);
            if ($local) {
                $raw = json_decode($local['raw_api_data'] ?? 'null', true);
                Response::json(['species' => array_merge(is_array($raw) ? $raw : [], [
                    'local_id' => $local['id'],
                    'external_id' => $local['external_api_id'],
                    'common_name' => $local['common_name'],
                    'scientific_name' => $local['scientific_name'],
                    'care_level' => $local['care_level'],
                    'watering_info' => $local['watering_info'],
                    'sunlight_info' => $local['sunlight_info'],
                    'climate_info' => $local['climate_info'],
                ])]);
            }
        }

        Response::notFound('Gatunek nie znaleziony');
    }

    public function import(Request $req): void
    {
        $data = $req->all();
        if (empty($data['common_name'])) Response::error('Brak nazwy', 422);
        $id = $this->api->importToLocal($data);
        Response::json(['species_id' => $id]);
    }
}
