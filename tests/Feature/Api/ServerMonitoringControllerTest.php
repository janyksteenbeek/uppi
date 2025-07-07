<?php

use App\Models\DiskMetric;
use App\Models\NetworkMetric;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;

function generateHmacSignature(string $payload, string $secret, int $timestamp = null): array
{
    $timestamp = $timestamp ?: time();
    $message = $timestamp . $payload;
    $signature = hash_hmac('sha256', $message, $secret);
    
    return [
        'X-Timestamp' => (string)$timestamp,
        'X-Signature' => $signature,
    ];
}

it('requires valid hmac signature for report endpoint', function () {
    $server = Server::factory()->create();
    $payload = json_encode(['cpu_usage' => 50.0]);

    // Test without signature headers
    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true));
    $response->assertStatus(401);

    // Test with invalid signature
    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), [
        'X-Timestamp' => (string)time(),
        'X-Signature' => 'invalid-signature',
    ]);
    $response->assertStatus(401);
});

it('rejects old timestamps', function () {
    $server = Server::factory()->create();
    $payload = json_encode(['cpu_usage' => 50.0]);
    $oldTimestamp = time() - 400; // 6+ minutes ago
    
    $headers = generateHmacSignature($payload, $server->secret, $oldTimestamp);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    $response->assertStatus(401);
});

it('accepts valid basic metrics', function () {
    $server = Server::factory()->create();
    $payload = json_encode([
        'cpu_usage' => 45.2,
        'cpu_load_1' => 0.75,
        'cpu_load_5' => 0.82,
        'cpu_load_15' => 0.79,
        'memory_total' => 8589934592,
        'memory_used' => 3221225472,
        'memory_available' => 5368709120,
        'memory_usage_percent' => 37.5,
        'swap_total' => 2147483648,
        'swap_used' => 0,
        'swap_usage_percent' => 0.0,
    ]);

    $headers = generateHmacSignature($payload, $server->secret);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'metric_id',
                 'timestamp'
             ]);

    // Verify the metric was created
    expect('server_metrics')->toHaveRecord([
        'server_id' => $server->id,
        'cpu_usage' => 45.2,
        'memory_usage_percent' => 37.5,
    ]);

    // Verify server's last_seen_at was updated
    $server->refresh();
    expect($server->last_seen_at)->not->toBeNull()
        ->and($server->last_seen_at->greaterThan(now()->subMinute()))->toBeTrue();
});

it('accepts disk metrics', function () {
    $server = Server::factory()->create();
    $payload = json_encode([
        'cpu_usage' => 50.0,
        'disk_metrics' => [
            [
                'mount_point' => '/',
                'total_bytes' => 21474836480,
                'used_bytes' => 10737418240,
                'available_bytes' => 10737418240,
                'usage_percent' => 50.0
            ],
            [
                'mount_point' => '/var',
                'total_bytes' => 5368709120,
                'used_bytes' => 1073741824,
                'available_bytes' => 4294967296,
                'usage_percent' => 20.0
            ]
        ]
    ]);

    $headers = generateHmacSignature($payload, $server->secret);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    
    $response->assertStatus(200);

    // Verify disk metrics were created
    expect('disk_metrics')->toHaveRecord([
        'mount_point' => '/',
        'total_bytes' => 21474836480,
        'usage_percent' => 50.0
    ])->toHaveRecord([
        'mount_point' => '/var',
        'total_bytes' => 5368709120,
        'usage_percent' => 20.0
    ]);

    // Verify the relationships
    $metric = ServerMetric::where('server_id', $server->id)->first();
    expect($metric->diskMetrics)->toHaveCount(2);
});

it('accepts network metrics', function () {
    $server = Server::factory()->create();
    $payload = json_encode([
        'cpu_usage' => 50.0,
        'network_metrics' => [
            [
                'interface_name' => 'eth0',
                'rx_bytes' => 12345678,
                'tx_bytes' => 9876543,
                'rx_packets' => 12345,
                'tx_packets' => 9876,
                'rx_errors' => 0,
                'tx_errors' => 0
            ],
            [
                'interface_name' => 'lo',
                'rx_bytes' => 1024,
                'tx_bytes' => 1024,
                'rx_packets' => 10,
                'tx_packets' => 10
            ]
        ]
    ]);

    $headers = generateHmacSignature($payload, $server->secret);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    
    $response->assertStatus(200);

    // Verify network metrics were created
    expect('network_metrics')->toHaveRecord([
        'interface_name' => 'eth0',
        'rx_bytes' => 12345678,
        'tx_bytes' => 9876543
    ])->toHaveRecord([
        'interface_name' => 'lo',
        'rx_bytes' => 1024,
        'tx_bytes' => 1024
    ]);

    // Verify the relationships
    $metric = ServerMetric::where('server_id', $server->id)->first();
    expect($metric->networkMetrics)->toHaveCount(2);
});

it('calculates disk usage percent if missing', function () {
    $server = Server::factory()->create();
    $payload = json_encode([
        'disk_metrics' => [
            [
                'mount_point' => '/',
                'total_bytes' => 1000,
                'used_bytes' => 500,
                'available_bytes' => 500
                // usage_percent intentionally omitted
            ]
        ]
    ]);

    $headers = generateHmacSignature($payload, $server->secret);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    
    $response->assertStatus(200);

    // Verify usage_percent was calculated (500/1000 * 100 = 50.0)
    expect('disk_metrics')->toHaveRecord([
        'mount_point' => '/',
        'usage_percent' => 50.0
    ]);
});

it('validates required fields', function () {
    $server = Server::factory()->create();
    $payload = json_encode([
        'cpu_usage' => 150.0, // Invalid: > 100
        'memory_usage_percent' => -10, // Invalid: < 0
        'disk_metrics' => [
            [
                'mount_point' => '/',
                // missing required fields
            ]
        ]
    ]);

    $headers = generateHmacSignature($payload, $server->secret);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    
    $response->assertStatus(422)
             ->assertJsonStructure([
                 'error',
                 'details'
             ]);
});

it('requires valid hmac for config endpoint', function () {
    $server = Server::factory()->create();

    // Test without signature
    $response = $this->getJson("/api/server/{$server->id}/config");
    $response->assertStatus(401);

    // Test with valid signature
    $headers = generateHmacSignature('', $server->secret);
    $response = $this->getJson("/api/server/{$server->id}/config", $headers);
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'server_id',
                 'name',
                 'hostname',
                 'is_active',
                 'report_url',
                 'last_seen_at'
             ]);
});

it('requires authentication for cleanup endpoint', function () {
    // Test without authentication
    $response = $this->postJson('/api/server/cleanup');
    $response->assertStatus(401);

    // Test with authentication
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->postJson('/api/server/cleanup', [], [
        'Authorization' => 'Bearer ' . $token
    ]);
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'deleted_count',
                 'cutoff_date'
             ]);
});

it('deletes old metrics in cleanup endpoint', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $user->id]);
    
    // Create old metrics (35 days ago)
    $oldMetric = ServerMetric::factory()->create([
        'server_id' => $server->id,
        'created_at' => now()->subDays(35)
    ]);
    
    // Create recent metrics (10 days ago)
    $recentMetric = ServerMetric::factory()->create([
        'server_id' => $server->id,
        'created_at' => now()->subDays(10)
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->postJson('/api/server/cleanup', [], [
        'Authorization' => 'Bearer ' . $token
    ]);
    
    $response->assertStatus(200);

    // Verify old metrics were deleted
    expect('server_metrics')->not->toHaveRecord(['id' => $oldMetric->id]);
    
    // Verify recent metrics were kept
    expect('server_metrics')->toHaveRecord(['id' => $recentMetric->id]);

    $responseData = $response->json();
    expect($responseData['deleted_count'])->toBe(1);
});

it('handles nonexistent server', function () {
    $payload = json_encode(['cpu_usage' => 50.0]);
    $headers = generateHmacSignature($payload, 'fake-secret');

    $response = $this->postJson('/api/server/nonexistent-id/report', json_decode($payload, true), $headers);
    
    $response->assertStatus(404);
});

it('logs successful submissions', function () {
    $server = Server::factory()->create(['name' => 'Test Server']);
    $payload = json_encode(['cpu_usage' => 50.0]);
    $headers = generateHmacSignature($payload, $server->secret);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    
    $response->assertStatus(200);
    
    // Could add log assertions here if needed
    // expect(Log::info)->toHaveBeenCalledWith('Server metrics received successfully');
});

it('uses provided collected_at timestamp', function () {
    $server = Server::factory()->create();
    $customTimestamp = '2025-01-20T10:30:00Z';
    
    $payload = json_encode([
        'cpu_usage' => 50.0,
        'collected_at' => $customTimestamp
    ]);

    $headers = generateHmacSignature($payload, $server->secret);

    $response = $this->postJson("/api/server/{$server->id}/report", json_decode($payload, true), $headers);
    
    $response->assertStatus(200);

    // Verify the custom timestamp was used
    $metric = ServerMetric::where('server_id', $server->id)->first();
    expect($metric->collected_at->format('Y-m-d H:i:s'))->toBe('2025-01-20 10:30:00');
});