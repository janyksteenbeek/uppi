<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' Server',
            'hostname' => fake()->domainName(),
            'ip_address' => fake()->ipv4(),
            'os' => fake()->randomElement([
                'Ubuntu 22.04 LTS',
                'CentOS 8',
                'Debian 11',
                'Red Hat Enterprise Linux 9',
                'Windows Server 2019',
                'Windows Server 2022',
                'Alpine Linux 3.16',
                'FreeBSD 13.1'
            ]),
            'secret' => Str::random(32),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'last_seen_at' => fake()->optional(0.9)->dateTimeBetween('-1 hour', 'now'),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the server is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the server is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the server was recently seen.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen_at' => now()->subMinutes(fake()->numberBetween(1, 4)),
        ]);
    }

    /**
     * Indicate that the server was not seen recently.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen_at' => now()->subMinutes(fake()->numberBetween(10, 60)),
        ]);
    }

    /**
     * Indicate that the server has never been seen.
     */
    public function neverSeen(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen_at' => null,
        ]);
    }
}