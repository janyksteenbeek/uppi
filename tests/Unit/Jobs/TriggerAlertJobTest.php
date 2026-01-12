<?php

use App\Enums\Checks\Status;
use App\Enums\Monitors\MonitorType;
use App\Jobs\TriggerAlertJob;
use App\Models\Anomaly;
use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an anomaly after consecutive failures', function () {
    $monitor = Monitor::factory()->create([
        'status' => Status::UNKNOWN,
        'consecutive_threshold' => 2,
        'is_enabled' => true,
        'type' => MonitorType::DUMMY,
    ]);

    // First failure
    $firstCheck = Check::factory()->create([
        'monitor_id' => $monitor->id,
        'status' => Status::FAIL,
        'checked_at' => now(),
    ]);

    (new TriggerAlertJob($firstCheck))->handle();

    $monitor->refresh();

    // After first failure: no anomaly yet, status still UNKNOWN (threshold not met)
    expect($monitor->anomalies->count())->toBe(0)
        ->and($monitor->status)->toBe(Status::UNKNOWN);

    // Second failure - should create anomaly and update status
    $secondCheck = Check::factory()->create([
        'monitor_id' => $monitor->id,
        'status' => Status::FAIL,
        'checked_at' => now()->addMinute(),
    ]);

    (new TriggerAlertJob($secondCheck))->handle();

    $monitor->refresh();

    // After second failure: anomaly created, status is now FAIL
    expect($monitor->anomalies->count())->toBe(1)
        ->and($monitor->status)->toBe(Status::FAIL);
});

it('associates checks with anomaly', function () {
    $monitor = Monitor::factory()->create([
        'status' => Status::UNKNOWN,
        'consecutive_threshold' => 2,
        'interval' => 1,
        'is_enabled' => true,
        'type' => MonitorType::DUMMY,
    ]);

    // Create two failing checks
    $checks = Check::factory()->count(2)->create([
        'monitor_id' => $monitor->id,
        'status' => Status::FAIL,
        'checked_at' => now(),
    ]);

    // Process both checks
    $checks->each(fn ($check) => (new TriggerAlertJob($check))->handle());

    $monitor->refresh();

    $anomaly = $monitor->anomalies->first();

    expect(value: $anomaly->checks->count())->toBe(2)
        ->and($anomaly->checks->pluck('id'))->toEqual($checks->pluck('id'));
});

it('closes anomaly after consecutive successes', function () {
    $monitor = Monitor::factory()->create([
        'status' => Status::UNKNOWN,
        'consecutive_threshold' => 2,
        'is_enabled' => true,
        'interval' => 1,
        'type' => MonitorType::DUMMY,
    ]);

    // Create initial failing checks to create anomaly
    $failingChecks = collect([
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinutes(3),
        ]),
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinutes(2),
        ]),
    ]);

    $failingChecks->each(fn ($check) => (new TriggerAlertJob($check))->handle());

    // Now create successful checks
    $successChecks = collect([
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::OK,
            'checked_at' => now()->subMinute(),
        ]),
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::OK,
            'checked_at' => now(),
        ]),
    ]);

    $successChecks->each(fn ($check) => (new TriggerAlertJob($check))->handle());

    $monitor->refresh();
    $anomaly = $monitor->anomalies->first();

    // After recovery threshold met: anomaly closed, status is now OK
    expect($anomaly->ended_at)->not->toBeNull()
        ->and($monitor->status)->toBe(Status::OK);
});

it('maintains anomaly during mixed status checks', function () {
    $monitor = Monitor::factory()->create([
        'status' => Status::UNKNOWN,
        'consecutive_threshold' => 2,
        'is_enabled' => true,
        'type' => MonitorType::DUMMY,
    ]);

    // Create initial failing checks to create anomaly
    collect([
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinutes(4),
        ]),
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinutes(3),
        ]),
    ])->each(fn ($check) => TriggerAlertJob::dispatchSync($check));

    // Add mixed status checks
    collect([
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::OK,
            'checked_at' => now()->subMinutes(2),
        ]),
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinute(),
        ]),
    ])->each(fn ($check) => TriggerAlertJob::dispatchSync($check));

    $monitor->refresh();
    $anomaly = $monitor->anomalies->first();

    // Anomaly still open, status still FAIL (recovery threshold not met due to mixed checks)
    expect($anomaly->ended_at)->toBeNull()
        ->and($anomaly->checks)->toHaveCount(count: 4)
        ->and($monitor->status)->toBe(Status::FAIL);
});

it('handles multiple anomalies for the same monitor', function () {
    $monitor = Monitor::factory()->create([
        'status' => Status::UNKNOWN,
        'consecutive_threshold' => 2,
    ]);

    // First anomaly
    collect([
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinutes(5),
        ]),
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinutes(4),
        ]),
    ])->each(fn ($check) => (new TriggerAlertJob($check))->handle());

    // Recovery
    collect([
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::OK,
            'checked_at' => now()->subMinutes(3),
        ]),
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::OK,
            'checked_at' => now()->subMinutes(2),
        ]),
    ])->each(fn ($check) => (new TriggerAlertJob($check))->handle());

    // Second anomaly
    collect([
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now()->subMinute(),
        ]),
        Check::factory()->create([
            'monitor_id' => $monitor->id,
            'status' => Status::FAIL,
            'checked_at' => now(),
        ]),
    ])->each(fn ($check) => (new TriggerAlertJob($check))->handle());

    // Two anomalies: one closed, one open. Status should be FAIL (second anomaly active)
    expect(Anomaly::count())->toBe(2)
        ->and(Anomaly::whereNotNull('ended_at')->count())->toBe(1)
        ->and(Anomaly::whereNull('ended_at')->count())->toBe(1);
});
