<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerMetric>
 */
class ServerMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $memoryTotal = fake()->numberBetween(1024 * 1024 * 1024, 64 * 1024 * 1024 * 1024); // 1GB to 64GB
        $memoryUsed = fake()->numberBetween(0, (int)($memoryTotal * 0.9)); // Up to 90% used
        $memoryAvailable = $memoryTotal - $memoryUsed;
        $memoryUsagePercent = $memoryTotal > 0 ? ($memoryUsed / $memoryTotal) * 100 : 0;

        $swapTotal = fake()->numberBetween(0, 8 * 1024 * 1024 * 1024); // 0 to 8GB swap
        $swapUsed = $swapTotal > 0 ? fake()->numberBetween(0, (int)($swapTotal * 0.5)) : 0; // Up to 50% swap used
        $swapUsagePercent = $swapTotal > 0 ? ($swapUsed / $swapTotal) * 100 : 0;

        return [
            'server_id' => Server::factory(),
            'cpu_usage' => fake()->randomFloat(2, 0, 100),
            'cpu_load_1' => fake()->randomFloat(2, 0, 10),
            'cpu_load_5' => fake()->randomFloat(2, 0, 10),
            'cpu_load_15' => fake()->randomFloat(2, 0, 10),
            'memory_total' => $memoryTotal,
            'memory_used' => $memoryUsed,
            'memory_available' => $memoryAvailable,
            'memory_usage_percent' => $memoryUsagePercent,
            'swap_total' => $swapTotal,
            'swap_used' => $swapUsed,
            'swap_usage_percent' => $swapUsagePercent,
            'collected_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * High CPU usage scenario.
     */
    public function highCpu(): static
    {
        return $this->state(fn (array $attributes) => [
            'cpu_usage' => fake()->randomFloat(2, 80, 100),
            'cpu_load_1' => fake()->randomFloat(2, 4, 10),
            'cpu_load_5' => fake()->randomFloat(2, 3, 8),
            'cpu_load_15' => fake()->randomFloat(2, 2, 6),
        ]);
    }

    /**
     * High memory usage scenario.
     */
    public function highMemory(): static
    {
        return $this->state(function (array $attributes) {
            $memoryTotal = $attributes['memory_total'];
            $memoryUsed = (int)($memoryTotal * fake()->randomFloat(2, 0.8, 0.95)); // 80-95% used
            $memoryAvailable = $memoryTotal - $memoryUsed;
            $memoryUsagePercent = ($memoryUsed / $memoryTotal) * 100;

            return [
                'memory_used' => $memoryUsed,
                'memory_available' => $memoryAvailable,
                'memory_usage_percent' => $memoryUsagePercent,
            ];
        });
    }

    /**
     * Low resource usage scenario.
     */
    public function lowUsage(): static
    {
        return $this->state(function (array $attributes) {
            $memoryTotal = $attributes['memory_total'];
            $memoryUsed = (int)($memoryTotal * fake()->randomFloat(2, 0.1, 0.3)); // 10-30% used
            $memoryAvailable = $memoryTotal - $memoryUsed;
            $memoryUsagePercent = ($memoryUsed / $memoryTotal) * 100;

            return [
                'cpu_usage' => fake()->randomFloat(2, 0, 20),
                'cpu_load_1' => fake()->randomFloat(2, 0, 1),
                'cpu_load_5' => fake()->randomFloat(2, 0, 1),
                'cpu_load_15' => fake()->randomFloat(2, 0, 1),
                'memory_used' => $memoryUsed,
                'memory_available' => $memoryAvailable,
                'memory_usage_percent' => $memoryUsagePercent,
            ];
        });
    }

    /**
     * Recent metric (collected within last hour).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Old metric (collected more than a day ago).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}