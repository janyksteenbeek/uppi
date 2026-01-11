<?php

use App\Models\Test;
use App\Models\TestRun;
use App\Models\TestStep;
use App\Models\User;
use App\Enums\Tests\TestFlowBlockType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $user = User::factory()->create();
    $test = Test::factory()->create(['user_id' => $user->id]);

    expect($test->user->id)->toBe($user->id);
});

it('has many steps', function () {
    $test = Test::factory()->create();
    TestStep::factory()->count(3)->create(['test_id' => $test->id]);

    expect($test->steps)->toHaveCount(3);
});

it('orders steps by sort_order', function () {
    $test = Test::factory()->create();

    TestStep::factory()->create(['test_id' => $test->id, 'sort_order' => 2]);
    TestStep::factory()->create(['test_id' => $test->id, 'sort_order' => 0]);
    TestStep::factory()->create(['test_id' => $test->id, 'sort_order' => 1]);

    $sortOrders = $test->steps->pluck('sort_order')->toArray();

    expect($sortOrders)->toBe([0, 1, 2]);
});

it('has many runs', function () {
    $test = Test::factory()->create();
    TestRun::factory()->count(3)->create(['test_id' => $test->id]);

    expect($test->runs)->toHaveCount(3);
});

it('has a last run relationship', function () {
    $test = Test::factory()->create();

    $oldRun = TestRun::factory()->create([
        'test_id' => $test->id,
        'started_at' => now()->subHour(),
    ]);

    $latestRun = TestRun::factory()->create([
        'test_id' => $test->id,
        'started_at' => now(),
    ]);

    expect($test->lastRun->id)->toBe($latestRun->id);
});

it('updates last run timestamp', function () {
    $test = Test::factory()->create(['last_run_at' => null]);

    expect($test->last_run_at)->toBeNull();

    $test->updateLastRun();

    expect($test->fresh()->last_run_at)->not->toBeNull();
});

it('extracts domain from entrypoint url', function () {
    $test = Test::factory()->create(['entrypoint_url' => 'https://example.com/page']);

    expect($test->domain)->toBe('example.com');
});

it('handles complex urls for domain extraction', function () {
    $test = Test::factory()->create(['entrypoint_url' => 'https://subdomain.example.co.uk:8080/path?query=1']);

    expect($test->domain)->toBe('subdomain.example.co.uk');
});
