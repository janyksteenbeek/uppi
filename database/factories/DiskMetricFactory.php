<?php

namespace Database\Factories;

use App\Models\ServerMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiskMetric>
 */
class DiskMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalBytes = fake()->numberBetween(10 * 1024 * 1024 * 1024, 2 * 1024 * 1024 * 1024 * 1024); // 10GB to 2TB
        $usedBytes = fake()->numberBetween(0, (int)($totalBytes * 0.9)); // Up to 90% used
        $availableBytes = $totalBytes - $usedBytes;
        $usagePercent = $totalBytes > 0 ? ($usedBytes / $totalBytes) * 100 : 0;

        return [
            'server_metric_id' => ServerMetric::factory(),
            'mount_point' => fake()->randomElement(['/', '/var', '/home', '/tmp', '/opt', '/usr/local']),
            'total_bytes' => $totalBytes,
            'used_bytes' => $usedBytes,
            'available_bytes' => $availableBytes,
            'usage_percent' => $usagePercent,
        ];
    }

    /**
     * Root filesystem.
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'mount_point' => '/',
        ]);
    }

    /**
     * High disk usage scenario.
     */
    public function highUsage(): static
    {
        return $this->state(function (array $attributes) {
            $totalBytes = $attributes['total_bytes'];
            $usedBytes = (int)($totalBytes * fake()->randomFloat(2, 0.8, 0.95)); // 80-95% used
            $availableBytes = $totalBytes - $usedBytes;
            $usagePercent = ($usedBytes / $totalBytes) * 100;

            return [
                'used_bytes' => $usedBytes,
                'available_bytes' => $availableBytes,
                'usage_percent' => $usagePercent,
            ];
        });
    }

    /**
     * Low disk usage scenario.
     */
    public function lowUsage(): static
    {
        return $this->state(function (array $attributes) {
            $totalBytes = $attributes['total_bytes'];
            $usedBytes = (int)($totalBytes * fake()->randomFloat(2, 0.05, 0.3)); // 5-30% used
            $availableBytes = $totalBytes - $usedBytes;
            $usagePercent = ($usedBytes / $totalBytes) * 100;

            return [
                'used_bytes' => $usedBytes,
                'available_bytes' => $availableBytes,
                'usage_percent' => $usagePercent,
            ];
        });
    }

    /**
     * Critical disk usage scenario (>95%).
     */
    public function critical(): static
    {
        return $this->state(function (array $attributes) {
            $totalBytes = $attributes['total_bytes'];
            $usedBytes = (int)($totalBytes * fake()->randomFloat(2, 0.95, 0.99)); // 95-99% used
            $availableBytes = $totalBytes - $usedBytes;
            $usagePercent = ($usedBytes / $totalBytes) * 100;

            return [
                'used_bytes' => $usedBytes,
                'available_bytes' => $availableBytes,
                'usage_percent' => $usagePercent,
            ];
        });
    }

    /**
     * Var partition.
     */
    public function var(): static
    {
        return $this->state(fn (array $attributes) => [
            'mount_point' => '/var',
        ]);
    }

    /**
     * Home partition.
     */
    public function home(): static
    {
        return $this->state(fn (array $attributes) => [
            'mount_point' => '/home',
        ]);
    }
}