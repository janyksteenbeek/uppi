<?php

namespace Database\Factories;

use App\Enums\Tests\TestFlowBlockType;
use App\Models\Test;
use App\Models\TestStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TestStep>
 */
class TestStepFactory extends Factory
{
    protected $model = TestStep::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'sort_order' => 0,
            'type' => TestFlowBlockType::WAIT_FOR_TEXT,
            'value' => fake()->words(3, true),
            'selector' => null,
        ];
    }

    public function visit(string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::VISIT,
            'value' => $url ?? fake()->url(),
        ]);
    }

    public function waitForText(string $text = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::WAIT_FOR_TEXT,
            'value' => $text ?? fake()->words(3, true),
        ]);
    }

    public function type(string $text = null, string $selector = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::TYPE,
            'value' => $text ?? fake()->email(),
            'selector' => $selector ?? '#email',
        ]);
    }

    public function press(string $buttonText = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::PRESS,
            'value' => $buttonText ?? 'Submit',
        ]);
    }

    public function back(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::BACK,
            'value' => null,
        ]);
    }

    public function forward(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::FORWARD,
            'value' => null,
        ]);
    }

    public function refresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::REFRESH,
            'value' => null,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TestFlowBlockType::SUCCESS,
            'value' => null,
        ]);
    }
}
