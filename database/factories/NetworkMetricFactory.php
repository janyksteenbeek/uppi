<?php

namespace Database\Factories;

use App\Models\ServerMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NetworkMetric>
 */
class NetworkMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_metric_id' => ServerMetric::factory(),
            'interface_name' => fake()->randomElement(['eth0', 'eth1', 'wlan0', 'lo', 'ens33', 'enp0s3']),
            'rx_bytes' => fake()->numberBetween(0, 100 * 1024 * 1024 * 1024), // 0 to 100GB
            'tx_bytes' => fake()->numberBetween(0, 100 * 1024 * 1024 * 1024), // 0 to 100GB
            'rx_packets' => fake()->numberBetween(0, 10000000),
            'tx_packets' => fake()->numberBetween(0, 10000000),
            'rx_errors' => fake()->numberBetween(0, 100),
            'tx_errors' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Ethernet interface.
     */
    public function ethernet(): static
    {
        return $this->state(fn (array $attributes) => [
            'interface_name' => fake()->randomElement(['eth0', 'eth1', 'ens33', 'enp0s3']),
        ]);
    }

    /**
     * WiFi interface.
     */
    public function wifi(): static
    {
        return $this->state(fn (array $attributes) => [
            'interface_name' => fake()->randomElement(['wlan0', 'wlan1', 'wlp2s0']),
        ]);
    }

    /**
     * Loopback interface.
     */
    public function loopback(): static
    {
        return $this->state(fn (array $attributes) => [
            'interface_name' => 'lo',
            'rx_errors' => 0,
            'tx_errors' => 0,
        ]);
    }

    /**
     * High traffic scenario.
     */
    public function highTraffic(): static
    {
        return $this->state(fn (array $attributes) => [
            'rx_bytes' => fake()->numberBetween(50 * 1024 * 1024 * 1024, 200 * 1024 * 1024 * 1024), // 50-200GB
            'tx_bytes' => fake()->numberBetween(50 * 1024 * 1024 * 1024, 200 * 1024 * 1024 * 1024), // 50-200GB
            'rx_packets' => fake()->numberBetween(5000000, 20000000),
            'tx_packets' => fake()->numberBetween(5000000, 20000000),
        ]);
    }

    /**
     * Low traffic scenario.
     */
    public function lowTraffic(): static
    {
        return $this->state(fn (array $attributes) => [
            'rx_bytes' => fake()->numberBetween(0, 1024 * 1024 * 1024), // 0-1GB
            'tx_bytes' => fake()->numberBetween(0, 1024 * 1024 * 1024), // 0-1GB
            'rx_packets' => fake()->numberBetween(0, 100000),
            'tx_packets' => fake()->numberBetween(0, 100000),
        ]);
    }

    /**
     * Interface with errors.
     */
    public function withErrors(): static
    {
        return $this->state(fn (array $attributes) => [
            'rx_errors' => fake()->numberBetween(10, 1000),
            'tx_errors' => fake()->numberBetween(10, 1000),
        ]);
    }

    /**
     * Clean interface (no errors).
     */
    public function clean(): static
    {
        return $this->state(fn (array $attributes) => [
            'rx_errors' => 0,
            'tx_errors' => 0,
        ]);
    }
}