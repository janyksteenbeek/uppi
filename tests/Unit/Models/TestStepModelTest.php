<?php

use App\Models\Test;
use App\Models\TestStep;
use App\Enums\Tests\TestFlowBlockType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a test', function () {
    $test = Test::factory()->create();
    $step = TestStep::factory()->create(['test_id' => $test->id]);

    expect($step->test->id)->toBe($test->id);
});

it('casts type to TestFlowBlockType enum', function () {
    $step = TestStep::factory()->create(['type' => 'visit']);

    expect($step->type)->toBeInstanceOf(TestFlowBlockType::class)
        ->and($step->type)->toBe(TestFlowBlockType::VISIT);
});

it('has delay helper methods', function () {
    $stepWithDelay = TestStep::factory()->create(['delay_ms' => 500]);
    $stepWithoutDelay = TestStep::factory()->create(['delay_ms' => null]);
    $stepWithZeroDelay = TestStep::factory()->create(['delay_ms' => 0]);

    expect($stepWithDelay->hasDelay())->toBeTrue()
        ->and($stepWithDelay->delay_seconds)->toBe(0.5)
        ->and($stepWithoutDelay->hasDelay())->toBeFalse()
        ->and($stepWithoutDelay->delay_seconds)->toBeNull()
        ->and($stepWithZeroDelay->hasDelay())->toBeFalse();
});

it('supports all step types', function () {
    $types = [
        TestFlowBlockType::VISIT,
        TestFlowBlockType::WAIT_FOR_TEXT,
        TestFlowBlockType::TYPE,
        TestFlowBlockType::PRESS,
        TestFlowBlockType::CLICK,
        TestFlowBlockType::CLICK_LINK,
        TestFlowBlockType::SELECT,
        TestFlowBlockType::CHECK,
        TestFlowBlockType::UNCHECK,
        TestFlowBlockType::BACK,
        TestFlowBlockType::FORWARD,
        TestFlowBlockType::REFRESH,
        TestFlowBlockType::SCREENSHOT,
        TestFlowBlockType::SUCCESS,
    ];

    foreach ($types as $type) {
        $step = TestStep::factory()->create(['type' => $type->value]);
        expect($step->type)->toBe($type);
    }
});

it('stores value and selector correctly', function () {
    $step = TestStep::factory()->type('test@example.com', '#email')->create();

    expect($step->value)->toBe('test@example.com')
        ->and($step->selector)->toBe('#email');
});
