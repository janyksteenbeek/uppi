<?php

use App\Models\Test;
use App\Models\TestRun;
use App\Models\TestRunStep;
use App\Models\TestStep;
use App\Enums\Tests\TestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a test', function () {
    $test = Test::factory()->create();
    $run = TestRun::factory()->create(['test_id' => $test->id]);

    expect($run->test->id)->toBe($test->id);
});

it('has many run steps', function () {
    $test = Test::factory()->create();
    $run = TestRun::factory()->create(['test_id' => $test->id]);

    $steps = TestStep::factory()->count(3)->create(['test_id' => $test->id]);

    foreach ($steps as $step) {
        TestRunStep::factory()->create([
            'test_run_id' => $run->id,
            'test_step_id' => $step->id,
        ]);
    }

    expect($run->runSteps)->toHaveCount(3);
});

it('can mark as running', function () {
    $run = TestRun::factory()->create(['status' => TestStatus::PENDING]);

    expect($run->status)->toBe(TestStatus::PENDING)
        ->and($run->started_at)->toBeNull();

    $run->markAsRunning();

    expect($run->fresh()->status)->toBe(TestStatus::RUNNING)
        ->and($run->fresh()->started_at)->not->toBeNull();
});

it('can mark as success', function () {
    $run = TestRun::factory()->running()->create();

    $run->markAsSuccess(1234);

    expect($run->fresh()->status)->toBe(TestStatus::SUCCESS)
        ->and($run->fresh()->duration_ms)->toBe(1234)
        ->and($run->fresh()->finished_at)->not->toBeNull();
});

it('can mark as failure', function () {
    $run = TestRun::factory()->running()->create();

    $run->markAsFailure(5678);

    expect($run->fresh()->status)->toBe(TestStatus::FAILURE)
        ->and($run->fresh()->duration_ms)->toBe(5678)
        ->and($run->fresh()->finished_at)->not->toBeNull();
});

it('can get failed step', function () {
    $test = Test::factory()->create();
    $run = TestRun::factory()->create(['test_id' => $test->id]);

    $step1 = TestStep::factory()->create(['test_id' => $test->id, 'sort_order' => 0]);
    $step2 = TestStep::factory()->create(['test_id' => $test->id, 'sort_order' => 1]);

    TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $step1->id,
        'status' => TestStatus::SUCCESS,
    ]);

    $failedRunStep = TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $step2->id,
        'status' => TestStatus::FAILURE,
        'error_message' => 'Element not found',
    ]);

    expect($run->getFailedStep()->id)->toBe($failedRunStep->id);
});

it('returns null when no failed step', function () {
    $test = Test::factory()->create();
    $run = TestRun::factory()->create(['test_id' => $test->id]);

    $step = TestStep::factory()->create(['test_id' => $test->id]);

    TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $step->id,
        'status' => TestStatus::SUCCESS,
    ]);

    expect($run->getFailedStep())->toBeNull();
});

it('can count completed steps', function () {
    $test = Test::factory()->create();
    $run = TestRun::factory()->create(['test_id' => $test->id]);

    $steps = TestStep::factory()->count(5)->create(['test_id' => $test->id]);

    // 3 successful, 1 failed, 1 pending
    TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $steps[0]->id,
        'status' => TestStatus::SUCCESS,
    ]);
    TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $steps[1]->id,
        'status' => TestStatus::SUCCESS,
    ]);
    TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $steps[2]->id,
        'status' => TestStatus::SUCCESS,
    ]);
    TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $steps[3]->id,
        'status' => TestStatus::FAILURE,
    ]);
    TestRunStep::factory()->create([
        'test_run_id' => $run->id,
        'test_step_id' => $steps[4]->id,
        'status' => TestStatus::PENDING,
    ]);

    expect($run->getCompletedStepsCount())->toBe(3);
});
