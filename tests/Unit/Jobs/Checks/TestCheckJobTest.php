<?php

use App\Enums\Checks\Status;
use App\Enums\Monitors\MonitorType;
use App\Enums\Tests\TestFlowBlockType;
use App\Enums\Tests\TestStatus;
use App\Jobs\Checks\TestCheckJob;
use App\Models\Monitor;
use App\Models\Test;
use App\Models\TestStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'feature_flags' => ['run-tests'],
    ]);

    $this->test = Test::factory()->create([
        'user_id' => $this->user->id,
        'entrypoint_url' => 'https://example.com',
    ]);

    $this->monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'type' => MonitorType::TEST,
        'address' => $this->test->id,
        'is_enabled' => true,
    ]);
});

it('fails when user does not have run-tests feature flag', function () {
    $this->user->update(['feature_flags' => []]);

    $job = new TestCheckJob($this->monitor);
    $job->handle();

    $check = $this->monitor->checks()->first();

    expect($check)->not->toBeNull()
        ->and($check->status)->toBe(Status::FAIL)
        ->and($check->output)->toContain('Tests feature is not enabled');
});

it('fails when no test is configured', function () {
    $this->monitor->update(['address' => 'non-existent-id']);

    $job = new TestCheckJob($this->monitor);
    $job->handle();

    $check = $this->monitor->checks()->first();

    expect($check)->not->toBeNull()
        ->and($check->status)->toBe(Status::FAIL)
        ->and($check->output)->toContain('No test configured');
});

it('fails when test has no steps', function () {
    // Test has no steps by default

    $job = new TestCheckJob($this->monitor);
    $job->handle();

    $check = $this->monitor->checks()->first();

    expect($check)->not->toBeNull()
        ->and($check->status)->toBe(Status::FAIL)
        ->and($check->output)->toContain('no steps configured');
});

it('creates a test run when test has steps', function () {
    TestStep::factory()->waitForText('Welcome')->create([
        'test_id' => $this->test->id,
        'sort_order' => 0,
    ]);

    expect($this->test->runs()->count())->toBe(0);

    // This will fail because there's no browser, but it should still create the run
    try {
        $job = new TestCheckJob($this->monitor);
        $job->handle();
    } catch (\Exception $e) {
        // Expected - no browser available
    }

    // A test run should have been created
    expect($this->test->runs()->count())->toBe(1);
});

it('creates run steps for each test step', function () {
    TestStep::factory()->waitForText('Welcome')->create([
        'test_id' => $this->test->id,
        'sort_order' => 0,
    ]);
    TestStep::factory()->press('Login')->create([
        'test_id' => $this->test->id,
        'sort_order' => 1,
    ]);
    TestStep::factory()->success()->create([
        'test_id' => $this->test->id,
        'sort_order' => 2,
    ]);

    try {
        $job = new TestCheckJob($this->monitor);
        $job->handle();
    } catch (\Exception $e) {
        // Expected - no browser available
    }

    $testRun = $this->test->runs()->first();

    expect($testRun)->not->toBeNull()
        ->and($testRun->runSteps()->count())->toBe(3);
});

it('respects step sort order', function () {
    // Create steps in non-sequential order
    TestStep::factory()->press('Submit')->create([
        'test_id' => $this->test->id,
        'sort_order' => 2,
    ]);
    TestStep::factory()->waitForText('Welcome')->create([
        'test_id' => $this->test->id,
        'sort_order' => 0,
    ]);
    TestStep::factory()->type('test@example.com', '#email')->create([
        'test_id' => $this->test->id,
        'sort_order' => 1,
    ]);

    $steps = $this->test->steps()->get();

    expect($steps[0]->sort_order)->toBe(0)
        ->and($steps[0]->type)->toBe(TestFlowBlockType::WAIT_FOR_TEXT)
        ->and($steps[1]->sort_order)->toBe(1)
        ->and($steps[1]->type)->toBe(TestFlowBlockType::TYPE)
        ->and($steps[2]->sort_order)->toBe(2)
        ->and($steps[2]->type)->toBe(TestFlowBlockType::PRESS);
});

it('associates monitor with test correctly', function () {
    expect($this->monitor->test->id)->toBe($this->test->id);
});

it('test belongs to correct user', function () {
    expect($this->test->user->id)->toBe($this->user->id);
});
