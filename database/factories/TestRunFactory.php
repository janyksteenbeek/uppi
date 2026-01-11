<?php

namespace Database\Factories;

use App\Enums\Tests\TestStatus;
use App\Models\Test;
use App\Models\TestRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TestRun>
 */
class TestRunFactory extends Factory
{
    protected $model = TestRun::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'status' => TestStatus::PENDING,
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
            'duration_ms' => fake()->numberBetween(1000, 10000),
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);
    }

    public function failure(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TestStatus::FAILURE,
            'duration_ms' => fake()->numberBetween(1000, 10000),
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);
    }
}
