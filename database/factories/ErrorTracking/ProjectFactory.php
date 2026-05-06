<?php

namespace Database\Factories\ErrorTracking;

use App\Models\ErrorTracking\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->randomNumber(5),
            'internal_id' => $this->faker->unique()->numberBetween(1000, 999_999),
            'public_key' => Str::random(32),
            'platform' => 'php',
            'is_active' => true,
        ];
    }
}
