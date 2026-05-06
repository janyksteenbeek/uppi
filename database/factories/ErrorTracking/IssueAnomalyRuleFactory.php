<?php

namespace Database\Factories\ErrorTracking;

use App\Enums\ErrorTracking\IssueAlertCondition;
use App\Models\ErrorTracking\IssueAnomalyRule;
use App\Models\ErrorTracking\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueAnomalyRuleFactory extends Factory
{
    protected $model = IssueAnomalyRule::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'name' => 'Default rule',
            'is_enabled' => true,
            'condition_type' => IssueAlertCondition::FIRST_SEEN,
            'threshold_count' => null,
            'threshold_window_minutes' => null,
            'throttle_window_minutes' => 60,
            'level_filter' => null,
            'environment_filter' => null,
        ];
    }

    public function threshold(int $count = 5, int $windowMinutes = 5): self
    {
        return $this->state(fn () => [
            'condition_type' => IssueAlertCondition::EVENT_COUNT_THRESHOLD,
            'threshold_count' => $count,
            'threshold_window_minutes' => $windowMinutes,
        ]);
    }

    public function regression(): self
    {
        return $this->state(fn () => [
            'condition_type' => IssueAlertCondition::REGRESSION,
        ]);
    }
}
