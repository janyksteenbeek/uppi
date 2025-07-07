<?php

use App\Models\NetworkMetric;
use App\Models\ServerMetric;

it('belongs to a server metric', function () {
    $serverMetric = ServerMetric::factory()->create();
    $networkMetric = NetworkMetric::factory()->create([
        'server_metric_id' => $serverMetric->id
    ]);

    expect($networkMetric->serverMetric)
        ->toBeInstanceOf(ServerMetric::class)
        ->and($networkMetric->serverMetric->id)->toBe($serverMetric->id);
});

it('formats bytes correctly', function () {
    $networkMetric = NetworkMetric::factory()->create([
        'rx_bytes' => 1024 * 1024 * 1024, // 1GB
        'tx_bytes' => 512 * 1024 * 1024,  // 512MB
    ]);

    expect($networkMetric->formatted_rx_bytes)->toBe('1.00 GB')
        ->and($networkMetric->formatted_tx_bytes)->toBe('512.00 MB');
});

it('calculates total bytes', function () {
    $networkMetric = NetworkMetric::factory()->create([
        'rx_bytes' => 1000,
        'tx_bytes' => 500,
    ]);

    expect($networkMetric->total_bytes)->toBe(1500)
        ->and($networkMetric->formatted_total_bytes)->toBe('1.46 KB');
});

it('handles zero bytes', function () {
    $networkMetric = NetworkMetric::factory()->create([
        'rx_bytes' => 0,
        'tx_bytes' => 0,
    ]);

    expect($networkMetric->formatted_rx_bytes)->toBe('0 B')
        ->and($networkMetric->formatted_tx_bytes)->toBe('0 B')
        ->and($networkMetric->total_bytes)->toBe(0)
        ->and($networkMetric->formatted_total_bytes)->toBe('0 B');
});

it('formats large bytes', function () {
    $networkMetric = NetworkMetric::factory()->create([
        'rx_bytes' => 2 * 1024 * 1024 * 1024 * 1024, // 2TB
        'tx_bytes' => 1024 * 1024 * 1024 * 1024,      // 1TB
    ]);

    expect($networkMetric->formatted_rx_bytes)->toBe('2.00 TB')
        ->and($networkMetric->formatted_tx_bytes)->toBe('1.00 TB')
        ->and($networkMetric->formatted_total_bytes)->toBe('3.00 TB');
});

it('casts attributes correctly', function () {
    $networkMetric = NetworkMetric::factory()->create([
        'rx_bytes' => '1073741824',
        'tx_bytes' => '536870912',
        'rx_packets' => '1000',
        'tx_packets' => '500',
        'rx_errors' => '5',
        'tx_errors' => '3',
    ]);

    expect($networkMetric->rx_bytes)->toBeInt()->toBe(1073741824)
        ->and($networkMetric->tx_bytes)->toBeInt()->toBe(536870912)
        ->and($networkMetric->rx_packets)->toBeInt()->toBe(1000)
        ->and($networkMetric->tx_packets)->toBeInt()->toBe(500)
        ->and($networkMetric->rx_errors)->toBeInt()->toBe(5)
        ->and($networkMetric->tx_errors)->toBeInt()->toBe(3);
});

it('factory generates valid data', function () {
    $networkMetric = NetworkMetric::factory()->create();

    expect($networkMetric->interface_name)->not->toBeNull()
        ->and($networkMetric->rx_bytes)->toBeGreaterThanOrEqual(0)
        ->and($networkMetric->tx_bytes)->toBeGreaterThanOrEqual(0);
    
    // Optional fields can be null
    if ($networkMetric->rx_packets !== null) {
        expect($networkMetric->rx_packets)->toBeGreaterThanOrEqual(0);
    }
    if ($networkMetric->tx_packets !== null) {
        expect($networkMetric->tx_packets)->toBeGreaterThanOrEqual(0);
    }
    if ($networkMetric->rx_errors !== null) {
        expect($networkMetric->rx_errors)->toBeGreaterThanOrEqual(0);
    }
    if ($networkMetric->tx_errors !== null) {
        expect($networkMetric->tx_errors)->toBeGreaterThanOrEqual(0);
    }
});

it('factory states work correctly', function () {
    $ethernetMetric = NetworkMetric::factory()->ethernet()->create();
    expect($ethernetMetric->interface_name)->toBeIn(['eth0', 'eth1', 'ens33', 'enp0s3']);

    $wifiMetric = NetworkMetric::factory()->wifi()->create();
    expect($wifiMetric->interface_name)->toBeIn(['wlan0', 'wlan1', 'wlp2s0']);

    $loopbackMetric = NetworkMetric::factory()->loopback()->create();
    expect($loopbackMetric->interface_name)->toBe('lo')
        ->and($loopbackMetric->rx_errors)->toBe(0)
        ->and($loopbackMetric->tx_errors)->toBe(0);

    $highTrafficMetric = NetworkMetric::factory()->highTraffic()->create();
    expect($highTrafficMetric->rx_bytes)->toBeGreaterThanOrEqual(50 * 1024 * 1024 * 1024)
        ->and($highTrafficMetric->tx_bytes)->toBeGreaterThanOrEqual(50 * 1024 * 1024 * 1024);

    $lowTrafficMetric = NetworkMetric::factory()->lowTraffic()->create();
    expect($lowTrafficMetric->rx_bytes)->toBeLessThanOrEqual(1024 * 1024 * 1024)
        ->and($lowTrafficMetric->tx_bytes)->toBeLessThanOrEqual(1024 * 1024 * 1024);

    $errorMetric = NetworkMetric::factory()->withErrors()->create();
    expect($errorMetric->rx_errors)->toBeGreaterThanOrEqual(10)
        ->and($errorMetric->tx_errors)->toBeGreaterThanOrEqual(10);

    $cleanMetric = NetworkMetric::factory()->clean()->create();
    expect($cleanMetric->rx_errors)->toBe(0)
        ->and($cleanMetric->tx_errors)->toBe(0);
});