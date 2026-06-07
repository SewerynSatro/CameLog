<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\FileUploadService;
use App\Services\PlantService;
use App\Repositories\PlantRepository;

class PlantController
{
    private PlantService $plants;

    public function __construct()
    {
        $this->plants = new PlantService();
    }

    public function index(Request $req): void
    {
        $userId = Auth::id();
        $filters = [
            'search' => $req->query('search'),
            'location' => $req->query('location'),
            'species' => $req->query('species'),
        ];
        $list = $this->plants->listForUser($userId, $filters);
        Response::json(['plants' => $list]);
    }

    public function show(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        $plant = $this->plants->findForUser($id, $userId);
        if (!$plant) Response::notFound('Roślina nie istnieje.');
        Response::json(['plant' => $plant]);
    }

    public function store(Request $req): void
    {
        $userId = Auth::id();
        $data = $req->all();
        $v = (new Validator($data))->required('name')->min('name', 1)->max('name', 120);
        if ($v->fails()) Response::error('Brak nazwy rośliny', 422, ['errors' => $v->errors()]);

        $payload = [
            'name' => $data['name'],
            'species_id' => !empty($data['species_id']) ? (int) $data['species_id'] : null,
            'custom_species_name' => $data['custom_species_name'] ?? null,
            'location' => $data['location'] ?? null,
            'planted_at' => !empty($data['planted_at']) ? $data['planted_at'] : null,
            'notes' => $data['notes'] ?? null,
            'watering_interval_days' => !empty($data['watering_interval_days']) ? (int) $data['watering_interval_days'] : null,
            'fertilizing_interval_days' => !empty($data['fertilizing_interval_days']) ? (int) $data['fertilizing_interval_days'] : null,
            'care_level' => $data['care_level'] ?? 'easy',
            'api_recommendations_used' => !empty($data['api_recommendations_used']),
            'health_status' => $data['health_status'] ?? 'healthy',
            'skip_auto_task' => !empty($data['skip_auto_task']),
        ];
        $plant = $this->plants->create($userId, $payload);
        Response::json(['plant' => $plant], 201);
    }

    public function update(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        $data = $req->all();
        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'species_id' => isset($data['species_id']) ? (int) $data['species_id'] : null,
            'custom_species_name' => $data['custom_species_name'] ?? null,
            'location' => $data['location'] ?? null,
            'planted_at' => $data['planted_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'watering_interval_days' => isset($data['watering_interval_days']) ? (int) $data['watering_interval_days'] : null,
            'fertilizing_interval_days' => isset($data['fertilizing_interval_days']) ? (int) $data['fertilizing_interval_days'] : null,
            'care_level' => $data['care_level'] ?? null,
            'api_recommendations_used' => isset($data['api_recommendations_used']) ? (bool) $data['api_recommendations_used'] : null,
            'health_status' => $data['health_status'] ?? null,
        ], fn($v) => $v !== null);
        $plant = $this->plants->update($id, $userId, $payload);
        if (!$plant) Response::notFound('Roślina nie istnieje');
        Response::json(['plant' => $plant]);
    }

    public function destroy(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        $ok = $this->plants->delete($id, $userId);
        if (!$ok) Response::error('Nie udało się usunąć rośliny albo roślina nie istnieje.', 404);
        Response::json(['ok' => true]);
    }

    public function uploadPhoto(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        $repo = new PlantRepository();
        $plant = $repo->findForUser($id, $userId);
        if (!$plant) Response::notFound('Roślina nie istnieje');
        $file = $req->file('photo');
        if (!$file) Response::error('Nie przesłano pliku', 422);
        $r = (new FileUploadService())->uploadPlantPhoto($file);
        if (!$r['ok']) Response::error($r['message'], 422);
        $repo->addPhoto($id, $r['path'], true);
        Response::json(['photo' => $r['path']]);
    }

    public function plantStats(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        Response::json(['stats' => $this->plants->plantStats($id, $userId)]);
    }
}
