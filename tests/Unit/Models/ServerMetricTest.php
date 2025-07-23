<?php

use App\Models\DiskMetric;
use App\Models\NetworkMetric;
use App\Models\Server;
use App\Models\ServerMetric;

it('belongs to a server', function () {
    $server = Server::factory()->create();
    $metric = ServerMetric::factory()->create(['server_id' => $server->id]);

    expect($metric->server)
        ->toBeInstanceOf(Server::class)
        ->and($metric->server->id)->toBe($server->id);
});

it('has many disk metrics', function () {
    $metric = ServerMetric::factory()->create();
    $disk1 = DiskMetric::factory()->create(['server_metric_id' => $metric->id]);
    $disk2 = DiskMetric::factory()->create(['server_metric_id' => $metric->id]);

    expect($metric->diskMetrics)
        ->toHaveCount(2)
        ->and($metric->diskMetrics->contains($disk1))->toBeTrue()
        ->and($metric->diskMetrics->contains($disk2))->toBeTrue();
});

it('has many network metrics', function () {
    $metric = ServerMetric::factory()->create();
    $network1 = NetworkMetric::factory()->create(['server_metric_id' => $metric->id]);
    $network2 = NetworkMetric::factory()->create(['server_metric_id' => $metric->id]);

    expect($metric->networkMetrics)
        ->toHaveCount(2)
        ->and($metric->networkMetrics->contains($network1))->toBeTrue()
        ->and($metric->networkMetrics->contains($network2))->toBeTrue();
});

it('formats memory correctly', function () {
    $metric = ServerMetric::factory()->create([
        'memory_total' => 8 * 1024 * 1024 * 1024, // 8GB
        'memory_used' => 4 * 1024 * 1024 * 1024,  // 4GB
    ]);

    expect($metric->formatted_memory_total)->toBe('8.00 GB')
        ->and($metric->formatted_memory_used)->toBe('4.00 GB');
});

it('formats swap correctly', function () {
    $metric = ServerMetric::factory()->create([
        'swap_total' => 2 * 1024 * 1024 * 1024, // 2GB
        'swap_used' => 1024 * 1024 * 1024,      // 1GB
    ]);

    expect($metric->formatted_swap_total)->toBe('2.00 GB')
        ->and($metric->formatted_swap_used)->toBe('1.00 GB');
});

it('casts attributes correctly', function () {
    $metric = ServerMetric::factory()->create([
        'cpu_usage' => '50.5',
        'cpu_load_1' => '1.5',
        'memory_total' => '8589934592',
        'memory_usage_percent' => '75.0',
        'collected_at' => '2025-01-20 10:30:00',
    ]);

    expect($metric->cpu_usage)->toBeFloat()->toBe(50.5)
        ->and($metric->cpu_load_1)->toBeFloat()->toBe(1.5)
        ->and($metric->memory_total)->toBeInt()->toBe(8589934592)
        ->and($metric->memory_usage_percent)->toBeFloat()->toBe(75.0)
        ->and($metric->collected_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('has for time range scope', function () {
    $server = Server::factory()->create();
    
    // Create metrics at different times
    $oldMetric = ServerMetric::factory()->create([
        'server_id' => $server->id,
        'collected_at' => '2025-01-01 12:00:00'
    ]);
    
    $middleMetric = ServerMetric::factory()->create([
        'server_id' => $server->id,
        'collected_at' => '2025-01-15 12:00:00'
    ]);
    
    $newMetric = ServerMetric::factory()->create([
        'server_id' => $server->id,
        'collected_at' => '2025-01-30 12:00:00'
    ]);

    // Test time range filtering
    $results = ServerMetric::forTimeRange('2025-01-10', '2025-01-20')->get();
    
    expect($results)
        ->toHaveCount(1)
        ->and($results->contains($middleMetric))->toBeTrue()
        ->and($results->contains($oldMetric))->toBeFalse()
        ->and($results->contains($newMetric))->toBeFalse();
});

it('has recent scope', function () {
    $server = Server::factory()->create();
    
    // Create old metric (25 hours ago)
    $oldMetric = ServerMetric::factory()->create([
        'server_id' => $server->id,
        'collected_at' => now()->subHours(25)
    ]);
    
    // Create recent metric (12 hours ago)
    $recentMetric = ServerMetric::factory()->create([
        'server_id' => $server->id,
        'collected_at' => now()->subHours(12)
    ]);

    // Test recent scope (default 24 hours)
    $results = ServerMetric::recent()->get();
    
    expect($results->contains($recentMetric))->toBeTrue()
        ->and($results->contains($oldMetric))->toBeFalse();

    // Test recent scope with custom hours
    $results = ServerMetric::recent(10)->get();
    
    expect($results->contains($recentMetric))->toBeFalse()
        ->and($results->contains($oldMetric))->toBeFalse();
});

it('factory generates valid data', function () {
    $metric = ServerMetric::factory()->create();

    // Test CPU metrics
    expect($metric->cpu_usage)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
        ->and($metric->cpu_load_1)->toBeGreaterThanOrEqual(0)
        ->and($metric->cpu_load_5)->toBeGreaterThanOrEqual(0)
        ->and($metric->cpu_load_15)->toBeGreaterThanOrEqual(0);

    // Test memory metrics
    expect($metric->memory_total)->toBeGreaterThanOrEqual(0)
        ->and($metric->memory_used)->toBeGreaterThanOrEqual(0)
        ->and($metric->memory_available)->toBeGreaterThanOrEqual(0)
        ->and($metric->memory_usage_percent)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);

    // Test swap metrics
    expect($metric->swap_total)->toBeGreaterThanOrEqual(0)
        ->and($metric->swap_used)->toBeGreaterThanOrEqual(0)
        ->and($metric->swap_usage_percent)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);

    // Test memory consistency
    expect($metric->memory_total)->toBe($metric->memory_used + $metric->memory_available)
        ->and($metric->collected_at)->not->toBeNull();
});

it('factory states work correctly', function () {
    $highCpuMetric = ServerMetric::factory()->highCpu()->create();
    expect($highCpuMetric->cpu_usage)->toBeGreaterThanOrEqual(80)
        ->and($highCpuMetric->cpu_load_1)->toBeGreaterThanOrEqual(4);

    $highMemoryMetric = ServerMetric::factory()->highMemory()->create();
    expect($highMemoryMetric->memory_usage_percent)->toBeGreaterThanOrEqual(80);

    $lowUsageMetric = ServerMetric::factory()->lowUsage()->create();
    expect($lowUsageMetric->cpu_usage)->toBeLessThanOrEqual(20)
        ->and($lowUsageMetric->memory_usage_percent)->toBeLessThanOrEqual(30);

    $recentMetric = ServerMetric::factory()->recent()->create();
    expect($recentMetric->collected_at->greaterThan(now()->subHour()))->toBeTrue();

    $oldMetric = ServerMetric::factory()->old()->create();
    expect($oldMetric->collected_at->lessThan(now()->subDay()))->toBeTrue();
});