<?php

namespace Database\Factories\ErrorTracking;

use App\Enums\ErrorTracking\IssueLevel;
use App\Enums\ErrorTracking\IssueStatus;
use App\Models\ErrorTracking\Issue;
use App\Models\ErrorTracking\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    protected $model = Issue::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'fingerprint_hash' => sha1($this->faker->unique()->uuid()),
            'title' => 'RuntimeException: '.$this->faker->sentence(3),
            'culprit' => 'App\\Http\\Controllers\\ExampleController->index',
            'platform' => 'php',
            'level' => IssueLevel::ERROR,
            'status' => IssueStatus::OPEN,
            'times_seen' => 1,
            'users_seen' => 0,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }

    public function resolved(): self
    {
        return $this->state(fn () => [
            'status' => IssueStatus::RESOLVED,
            'resolved_at' => now()->subHour(),
        ]);
    }
}
