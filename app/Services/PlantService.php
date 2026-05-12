<?php
namespace App\Services;

use App\Repositories\HistoryRepository;
use App\Repositories\PlantRepository;
use App\Repositories\TaskRepository;

class PlantService
{
    private PlantRepository $plants;
    private TaskRepository $tasks;
    private HistoryRepository $history;

    public function __construct()
    {
        $this->plants = new PlantRepository();
        $this->tasks = new TaskRepository();
        $this->history = new HistoryRepository();
    }

    public function create(int $userId, array $data): array
    {
        $plantData = array_merge($data, ['user_id' => $userId]);
        $id = $this->plants->create($plantData);

        // Auto-utworzenie pierwszego taska podlewania jeśli interwał jest podany
        if (!empty($data['watering_interval_days']) && empty($data['skip_auto_task'])) {
            $due = $this->getNextDueDate((int) $data['watering_interval_days']);
            $this->tasks->create([
                'user_id' => $userId,
                'plant_id' => $id,
                'type' => 'watering',
                'title' => 'Podlewanie: ' . $data['name'],
                'description' => 'Automatyczne przypomnienie o podlewaniu.',
                'due_date' => $due,
                'status' => 'pending',
                'repeat_interval_days' => (int) $data['watering_interval_days'],
                'priority' => 'normal',
            ]);
        }
        if (!empty($data['fertilizing_interval_days']) && empty($data['skip_auto_task'])) {
            $due = $this->getNextDueDate((int) $data['fertilizing_interval_days']);
            $this->tasks->create([
                'user_id' => $userId,
                'plant_id' => $id,
                'type' => 'fertilizing',
                'title' => 'Nawożenie: ' . $data['name'],
                'description' => 'Automatyczne przypomnienie o nawożeniu.',
                'due_date' => $due,
                'status' => 'pending',
                'repeat_interval_days' => (int) $data['fertilizing_interval_days'],
                'priority' => 'normal',
            ]);
        }
        return $this->plants->findForUser($id, $userId);
    }

    public function update(int $id, int $userId, array $data): ?array
    {
        $this->plants->update($id, $userId, $data);
        return $this->plants->findForUser($id, $userId);
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->plants->delete($id, $userId);
    }

    public function listForUser(int $userId, array $filters = []): array
    {
        return $this->plants->listForUser($userId, $filters);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->plants->findForUser($id, $userId);
    }

    public function plantStats(int $plantId, int $userId, int $days = 30): array
    {
        $rows = $this->history->listForPlant($plantId, $userId, 1000);
        $cnt = ['watering' => 0, 'fertilizing' => 0, 'pruning' => 0, 'repotting' => 0, 'misting' => 0, 'custom' => 0];
        $cutoff = strtotime("-$days days");
        foreach ($rows as $r) {
            if (strtotime($r['performed_at']) < $cutoff) continue;
            if (isset($cnt[$r['type']])) $cnt[$r['type']]++;
        }
        return $cnt;
    }

    private function getNextDueDate(int $intervalDays): string
    {
        return (new \DateTimeImmutable('today'))->modify("+$intervalDays days")->format('Y-m-d H:i:s');
    }
}
