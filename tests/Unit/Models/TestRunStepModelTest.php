<?php

use App\Models\Test;
use App\Models\TestRun;
use App\Models\TestRunStep;
use App\Models\TestStep;
use App\Enums\Tests\TestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->test = Test::factory()->create();
    $this->testRun = TestRun::factory()->create(['test_id' => $this->test->id]);
    $this->testStep = TestStep::factory()->create(['test_id' => $this->test->id]);
});

it('belongs to a test run', function () {
    $runStep = TestRunStep::factory()->create([
        'test_run_id' => $this->testRun->id,
        'test_step_id' => $this->testStep->id,
    ]);

    expect($runStep->testRun->id)->toBe($this->testRun->id);
});

it('belongs to a test step', function () {
    $runStep = TestRunStep::factory()->create([
        'test_run_id' => $this->testRun->id,
        'test_step_id' => $this->testStep->id,
    ]);

    expect($runStep->testStep->id)->toBe($this->testStep->id);
});

it('can mark as running', function () {
    $runStep = TestRunStep::factory()->create([
        'test_run_id' => $this->testRun->id,
        'test_step_id' => $this->testStep->id,
        'status' => TestStatus::PENDING,
    ]);

    expect($runStep->status)->toBe(TestStatus::PENDING)
        ->and($runStep->started_at)->toBeNull();

    $runStep->markAsRunning();

    expect($runStep->fresh()->status)->toBe(TestStatus::RUNNING)
        ->and($runStep->fresh()->started_at)->not->toBeNull();
});

it('can mark as success', function () {
    $runStep = TestRunStep::factory()->create([
        'test_run_id' => $this->testRun->id,
        'test_step_id' => $this->testStep->id,
        'status' => TestStatus::RUNNING,
    ]);

    $runStep->markAsSuccess(150);

    expect($runStep->fresh()->status)->toBe(TestStatus::SUCCESS)
        ->and($runStep->fresh()->duration_ms)->toBe(150)
        ->and($runStep->fresh()->finished_at)->not->toBeNull();
});

it('can mark as failure with all details', function () {
    $runStep = TestRunStep::factory()->create([
        'test_run_id' => $this->testRun->id,
        'test_step_id' => $this->testStep->id,
        'status' => TestStatus::RUNNING,
    ]);

    $runStep->markAsFailure(
        errorMessage: 'Element not found: #submit-button',
        durationMs: 500,
        screenshotPath: 'screenshots/test-123.png',
        htmlSnapshot: '<html><body>Page content</body></html>'
    );

    $fresh = $runStep->fresh();

    expect($fresh->status)->toBe(TestStatus::FAILURE)
        ->and($fresh->duration_ms)->toBe(500)
        ->and($fresh->error_message)->toBe('Element not found: #submit-button')
        ->and($fresh->screenshot_path)->toBe('screenshots/test-123.png')
        ->and($fresh->html_snapshot)->toBe('<html><body>Page content</body></html>')
        ->and($fresh->finished_at)->not->toBeNull();
});

it('can mark as failure with minimal details', function () {
    $runStep = TestRunStep::factory()->create([
        'test_run_id' => $this->testRun->id,
        'test_step_id' => $this->testStep->id,
        'status' => TestStatus::RUNNING,
    ]);

    $runStep->markAsFailure('Timeout', 30000);

    $fresh = $runStep->fresh();

    expect($fresh->status)->toBe(TestStatus::FAILURE)
        ->and($fresh->error_message)->toBe('Timeout')
        ->and($fresh->duration_ms)->toBe(30000)
        ->and($fresh->screenshot_path)->toBeNull()
        ->and($fresh->html_snapshot)->toBeNull();
});
