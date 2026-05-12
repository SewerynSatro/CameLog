<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\TaskRepository;
use App\Repositories\HistoryRepository;
use App\Services\TaskService;

class TaskController
{
    private TaskService $service;
    private TaskRepository $repo;
    private HistoryRepository $history;

    public function __construct()
    {
        $this->service = new TaskService();
        $this->repo = new TaskRepository();
        $this->history = new HistoryRepository();
    }

    public function index(Request $req): void
    {
        $userId = Auth::id();
        $filters = [
            'type' => $req->query('type'),
            'status' => $req->query('status'),
            'plant_id' => $req->query('plant_id'),
            'due' => $req->query('due'),
        ];
        Response::json(['tasks' => $this->repo->listForUser($userId, array_filter($filters))]);
    }

    public function today(Request $req): void
    {
        $userId = Auth::id();
        Response::json(['tasks' => $this->repo->listForUser($userId, ['due' => 'today'])]);
    }

    public function incoming(Request $req): void
    {
        $userId = Auth::id();
        Response::json(['tasks' => $this->repo->listForUser($userId, ['due' => 'incoming'])]);
    }

    public function overdue(Request $req): void
    {
        $userId = Auth::id();
        Response::json(['tasks' => $this->repo->listForUser($userId, ['due' => 'overdue'])]);
    }

    public function plantTasks(Request $req): void
    {
        $userId = Auth::id();
        $plantId = (int) $req->param('plantId');
        Response::json(['tasks' => $this->repo->listForUser($userId, ['plant_id' => $plantId])]);
    }

    public function createForPlant(Request $req): void
    {
        $userId = Auth::id();
        $plantId = (int) $req->param('plantId');
        $data = $req->all();
        $data['plant_id'] = $plantId;
        $this->doStore($userId, $data);
    }

    public function store(Request $req): void
    {
        $userId = Auth::id();
        $this->doStore($userId, $req->all());
    }

    private function doStore(int $userId, array $data): void
    {
        $v = (new Validator($data))
            ->required('plant_id')
            ->required('type')
            ->in('type', ['watering', 'fertilizing', 'pruning', 'repotting', 'misting', 'custom'])
            ->required('title')
            ->required('due_date');
        if ($v->fails()) Response::error('Nieprawidłowe dane', 422, ['errors' => $v->errors()]);

        // Normalizuj datę
        $due = $data['due_date'];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
            $due .= ' 09:00:00';
        }
        $payload = [
            'plant_id' => (int) $data['plant_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $due,
            'status' => $data['status'] ?? 'pending',
            'repeat_interval_days' => !empty($data['repeat_interval_days']) ? (int) $data['repeat_interval_days'] : null,
            'priority' => $data['priority'] ?? 'normal',
        ];
        $task = $this->service->create($userId, $payload);
        Response::json(['task' => $task], 201);
    }

    public function update(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        $data = $req->all();
        $task = $this->service->update($id, $userId, $data);
        if (!$task) Response::notFound();
        Response::json(['task' => $task]);
    }

    public function complete(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        $note = $req->input('note');
        $task = $this->service->complete($id, $userId, $note);
        if (!$task) Response::notFound();
        Response::json(['task' => $task]);
    }

    public function skip(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        $task = $this->service->skip($id, $userId);
        if (!$task) Response::notFound();
        Response::json(['task' => $task]);
    }

    public function destroy(Request $req): void
    {
        $userId = Auth::id();
        $id = (int) $req->param('id');
        Response::json(['ok' => $this->service->delete($id, $userId)]);
    }

    public function plantHistory(Request $req): void
    {
        $userId = Auth::id();
        $plantId = (int) $req->param('plantId');
        Response::json(['history' => $this->history->listForPlant($plantId, $userId)]);
    }

    public function addPlantHistory(Request $req): void
    {
        $userId = Auth::id();
        $plantId = (int) $req->param('plantId');
        $data = $req->all();
        $v = (new Validator($data))
            ->required('type')
            ->in('type', ['watering', 'fertilizing', 'pruning', 'repotting', 'misting', 'custom']);
        if ($v->fails()) Response::error('Nieprawidłowy typ', 422, ['errors' => $v->errors()]);
        $id = $this->history->create([
            'user_id' => $userId,
            'plant_id' => $plantId,
            'task_id' => $data['task_id'] ?? null,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'performed_at' => $data['performed_at'] ?? date('Y-m-d H:i:s'),
        ]);
        Response::json(['id' => $id], 201);
    }
}
