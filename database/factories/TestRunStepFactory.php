<?php

namespace Database\Factories;

use App\Enums\Tests\TestStatus;
use App\Models\TestRun;
use App\Models\TestRunStep;
use App\Models\TestStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TestRunStep>
 */
class TestRunStepFactory extends Factory
{
    protected $model = TestRunStep::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_run_id' => TestRun::factory(),
            'test_step_id' => TestStep::factory(),
            'sort_order' => 0,
            'status' => TestStatus::PENDING,
            'error_message' => null,
            'screenshot_path' => null,
            'duration_ms' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TestStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TestStatus::SUCCESS,
            'duration_ms' => fake()->numberBetween(100, 2000),
            'started_at' => now()->subMilliseconds(fake()->numberBetween(100, 2000)),
            'finished_at' => now(),
        ]);
    }

    public function failure(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TestStatus::FAILURE,
            'error_message' => 'Element not found',
            'duration_ms' => fake()->numberBetween(100, 2000),
            'started_at' => now()->subMilliseconds(fake()->numberBetween(100, 2000)),
            'finished_at' => now(),
        ]);
    }
}
