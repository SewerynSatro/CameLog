<?php
namespace App\Services;

use App\Repositories\HistoryRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\TaskRepository;

class TaskService
{
    private TaskRepository $tasks;
    private HistoryRepository $history;
    private NotificationRepository $notifications;

    public function __construct()
    {
        $this->tasks = new TaskRepository();
        $this->history = new HistoryRepository();
        $this->notifications = new NotificationRepository();
    }

    public function create(int $userId, array $data): array
    {
        $data['user_id'] = $userId;
        $id = $this->tasks->create($data);
        return $this->tasks->findForUser($id, $userId);
    }

    public function update(int $id, int $userId, array $data): ?array
    {
        $this->tasks->update($id, $userId, $data);
        return $this->tasks->findForUser($id, $userId);
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->tasks->delete($id, $userId);
    }

    /**
     * Oznacza task jako wykonany, dodaje wpis do historii i jeśli task jest cykliczny -
     * tworzy nowy task na kolejny termin.
     */
    public function complete(int $id, int $userId, ?string $note = null): ?array
    {
        $task = $this->tasks->findForUser($id, $userId);
        if (!$task) return null;
        if ($task['status'] === 'done') return $task;

        $now = date('Y-m-d H:i:s');
        $this->tasks->update($id, $userId, [
            'status' => 'done',
            'completed_at' => $now,
        ]);
        $this->history->create([
            'user_id' => $userId,
            'plant_id' => $task['plant_id'],
            'task_id' => $task['id'],
            'type' => $task['type'],
            'note' => $note,
            'performed_at' => $now,
        ]);

        // Automatyczna poprawa statusu rośliny po wykonaniu zadania
        $plantRepo = new \App\Repositories\PlantRepository();
        $plant = $plantRepo->findForUser($task['plant_id'], $userId);
        if ($plant && $plant['health_status'] === 'needs_attention') {
            $plantRepo->update($task['plant_id'], $userId, ['health_status' => 'healthy']);
        }

        // Jeśli task jest cykliczny – utwórz kolejny.
        if (!empty($task['repeat_interval_days'])) {
            $nextDue = (new \DateTimeImmutable($task['due_date']))
                ->modify('+' . (int) $task['repeat_interval_days'] . ' days')
                ->format('Y-m-d H:i:s');
            // Jeśli nowy due jest w przeszłości, ustaw go na za "interval" dni od teraz.
            if (strtotime($nextDue) < time()) {
                $nextDue = (new \DateTimeImmutable('today'))
                    ->modify('+' . (int) $task['repeat_interval_days'] . ' days')
                    ->format('Y-m-d H:i:s');
            }
            $this->tasks->create([
                'user_id' => $userId,
                'plant_id' => $task['plant_id'],
                'type' => $task['type'],
                'title' => $task['title'],
                'description' => $task['description'],
                'due_date' => $nextDue,
                'status' => 'pending',
                'repeat_interval_days' => (int) $task['repeat_interval_days'],
                'priority' => $task['priority'] ?? 'normal',
            ]);
        }
        return $this->tasks->findForUser($id, $userId);
    }

    public function skip(int $id, int $userId): ?array
    {
        $task = $this->tasks->findForUser($id, $userId);
        if (!$task) return null;
        $this->tasks->update($id, $userId, ['status' => 'skipped']);

        // Jeśli cykliczny – utwórz kolejny.
        if (!empty($task['repeat_interval_days'])) {
            $nextDue = (new \DateTimeImmutable('today'))
                ->modify('+' . (int) $task['repeat_interval_days'] . ' days')
                ->format('Y-m-d H:i:s');
            $this->tasks->create([
                'user_id' => $userId,
                'plant_id' => $task['plant_id'],
                'type' => $task['type'],
                'title' => $task['title'],
                'description' => $task['description'],
                'due_date' => $nextDue,
                'status' => 'pending',
                'repeat_interval_days' => (int) $task['repeat_interval_days'],
                'priority' => $task['priority'] ?? 'normal',
            ]);
        }
        return $this->tasks->findForUser($id, $userId);
    }
}
