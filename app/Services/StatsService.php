<?php
namespace App\Services;

use App\Repositories\HistoryRepository;
use App\Repositories\PlantRepository;
use App\Repositories\TaskRepository;

class StatsService
{
    private TaskRepository $tasks;
    private HistoryRepository $history;
    private PlantRepository $plants;

    public function __construct()
    {
        $this->tasks = new TaskRepository();
        $this->history = new HistoryRepository();
        $this->plants = new PlantRepository();
    }

    public function overview(int $userId): array
    {
        $taskStats = $this->tasks->statsForUser($userId);
        $historyStats = $this->history->statsForUser($userId, 30);
        $plantsCount = $this->plants->countForUser($userId);
        $topPlant = $this->history->topPlant($userId);
        $daily = $this->history->dailyActivity($userId, 30);

        // Wypełnij brakujące dni zerami dla wykresu
        $series = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $cnt = 0;
            foreach ($daily as $row) {
                if ($row['d'] === $d) { $cnt = (int) $row['cnt']; break; }
            }
            $series[] = ['date' => $d, 'count' => $cnt];
        }

        $totalActions = $historyStats['total'];
        $byType = [
            'watering' => $historyStats['watering'] + $historyStats['misting'],
            'fertilizing' => $historyStats['fertilizing'],
            'pruning' => $historyStats['pruning'],
            'repotting' => $historyStats['repotting'],
        ];
        $percentages = [];
        foreach ($byType as $k => $v) {
            $percentages[$k] = $totalActions > 0 ? round(($v / $totalActions) * 100) : 0;
        }

        $typeBreakdown = [];
        foreach ($byType as $type => $count) {
            $typeBreakdown[] = ['type' => $type, 'count' => $count];
        }

        return [
            'plants_count' => $plantsCount,
            'tasks_today' => $taskStats['today'],
            'tasks_overdue' => $taskStats['overdue'],
            'tasks_done_week' => $taskStats['upcoming_week'],
            'tasks_upcoming_week' => $taskStats['upcoming_week'],
            'today_tasks' => $taskStats['today'],
            'overdue_tasks' => $taskStats['overdue'],
            'week_done_tasks' => $taskStats['week_done'],
            'completed_tasks_total' => $totalActions,
            'tasks_done' => $totalActions,
            'watering_count' => $byType['watering'],
            'fertilizing_count' => $byType['fertilizing'],
            'pruning_count' => $byType['pruning'],
            'repotting_count' => $byType['repotting'],
            'top_plant' => $topPlant,
            'daily_activity' => $series,
            'task_type_percentages' => $percentages,
            'type_breakdown' => $typeBreakdown,
        ];
    }
}
