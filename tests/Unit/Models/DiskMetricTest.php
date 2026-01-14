<?php

use App\Models\DiskMetric;
use App\Models\ServerMetric;

it('belongs to a server metric', function () {
    $serverMetric = ServerMetric::factory()->create();
    $diskMetric = DiskMetric::factory()->create([
        'server_metric_id' => $serverMetric->id
    ]);

    expect($diskMetric->serverMetric)
        ->toBeInstanceOf(ServerMetric::class)
        ->and($diskMetric->serverMetric->id)->toBe($serverMetric->id);
});

it('formats bytes correctly', function () {
    $diskMetric = DiskMetric::factory()->create([
        'total_bytes' => 1024 * 1024 * 1024, // 1GB
        'used_bytes' => 512 * 1024 * 1024,   // 512MB
        'available_bytes' => 512 * 1024 * 1024, // 512MB
    ]);

    expect($diskMetric->formatted_total)->toBe('1.00 GB')
        ->and($diskMetric->formatted_used)->toBe('512.00 MB')
        ->and($diskMetric->formatted_available)->toBe('512.00 MB');
});

it('formats small bytes', function () {
    $diskMetric = DiskMetric::factory()->create([
        'total_bytes' => 1024,
        'used_bytes' => 512,
        'available_bytes' => 512,
    ]);

    expect($diskMetric->formatted_total)->toBe('1.00 KB')
        ->and($diskMetric->formatted_used)->toBe('512.00 B')
        ->and($diskMetric->formatted_available)->toBe('512.00 B');
});

it('formats large bytes', function () {
    $diskMetric = DiskMetric::factory()->create([
        'total_bytes' => 2 * 1024 * 1024 * 1024 * 1024, // 2TB
        'used_bytes' => 1024 * 1024 * 1024 * 1024,      // 1TB
        'available_bytes' => 1024 * 1024 * 1024 * 1024, // 1TB
    ]);

    expect($diskMetric->formatted_total)->toBe('2.00 TB')
        ->and($diskMetric->formatted_used)->toBe('1.00 TB')
        ->and($diskMetric->formatted_available)->toBe('1.00 TB');
});

it('handles zero bytes', function () {
    $diskMetric = DiskMetric::factory()->create([
        'total_bytes' => 0,
        'used_bytes' => 0,
        'available_bytes' => 0,
    ]);

    expect($diskMetric->formatted_total)->toBe('0 B')
        ->and($diskMetric->formatted_used)->toBe('0 B')
        ->and($diskMetric->formatted_available)->toBe('0 B');
});

it('casts attributes correctly', function () {
    $diskMetric = DiskMetric::factory()->create([
        'total_bytes' => '1073741824',
        'used_bytes' => '536870912',
        'available_bytes' => '536870912',
        'usage_percent' => '50.0',
    ]);

    expect($diskMetric->total_bytes)->toBeInt()->toBe(1073741824)
        ->and($diskMetric->used_bytes)->toBeInt()->toBe(536870912)
        ->and($diskMetric->available_bytes)->toBeInt()->toBe(536870912)
        ->and($diskMetric->usage_percent)->toBeFloat()->toBe(50.0);
});

it('factory generates valid data', function () {
    $diskMetric = DiskMetric::factory()->create();

    expect($diskMetric->mount_point)->not->toBeNull()
        ->and($diskMetric->total_bytes)->toBeGreaterThanOrEqual(0)
        ->and($diskMetric->used_bytes)->toBeGreaterThanOrEqual(0)
        ->and($diskMetric->available_bytes)->toBeGreaterThanOrEqual(0)
        ->and($diskMetric->usage_percent)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
    
    // Verify bytes add up correctly
    expect($diskMetric->total_bytes)->toBe($diskMetric->used_bytes + $diskMetric->available_bytes);
});