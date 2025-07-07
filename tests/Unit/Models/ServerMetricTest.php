<?php

namespace Tests\Unit\Models;

use App\Models\DiskMetric;
use App\Models\NetworkMetric;
use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_metric_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $metric = ServerMetric::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $metric->server);
        $this->assertEquals($server->id, $metric->server->id);
    }

    public function test_server_metric_has_many_disk_metrics(): void
    {
        $metric = ServerMetric::factory()->create();
        $disk1 = DiskMetric::factory()->create(['server_metric_id' => $metric->id]);
        $disk2 = DiskMetric::factory()->create(['server_metric_id' => $metric->id]);

        $this->assertCount(2, $metric->diskMetrics);
        $this->assertTrue($metric->diskMetrics->contains($disk1));
        $this->assertTrue($metric->diskMetrics->contains($disk2));
    }

    public function test_server_metric_has_many_network_metrics(): void
    {
        $metric = ServerMetric::factory()->create();
        $network1 = NetworkMetric::factory()->create(['server_metric_id' => $metric->id]);
        $network2 = NetworkMetric::factory()->create(['server_metric_id' => $metric->id]);

        $this->assertCount(2, $metric->networkMetrics);
        $this->assertTrue($metric->networkMetrics->contains($network1));
        $this->assertTrue($metric->networkMetrics->contains($network2));
    }

    public function test_server_metric_formats_memory_correctly(): void
    {
        $metric = ServerMetric::factory()->create([
            'memory_total' => 8 * 1024 * 1024 * 1024, // 8GB
            'memory_used' => 4 * 1024 * 1024 * 1024,  // 4GB
        ]);

        $this->assertEquals('8.00 GB', $metric->formatted_memory_total);
        $this->assertEquals('4.00 GB', $metric->formatted_memory_used);
    }

    public function test_server_metric_formats_swap_correctly(): void
    {
        $metric = ServerMetric::factory()->create([
            'swap_total' => 2 * 1024 * 1024 * 1024, // 2GB
            'swap_used' => 1024 * 1024 * 1024,      // 1GB
        ]);

        $this->assertEquals('2.00 GB', $metric->formatted_swap_total);
        $this->assertEquals('1.00 GB', $metric->formatted_swap_used);
    }

    public function test_server_metric_casts_attributes_correctly(): void
    {
        $metric = ServerMetric::factory()->create([
            'cpu_usage' => '50.5',
            'cpu_load_1' => '1.5',
            'memory_total' => '8589934592',
            'memory_usage_percent' => '75.0',
            'collected_at' => '2025-01-20 10:30:00',
        ]);

        $this->assertIsFloat($metric->cpu_usage);
        $this->assertIsFloat($metric->cpu_load_1);
        $this->assertIsInt($metric->memory_total);
        $this->assertIsFloat($metric->memory_usage_percent);
        $this->assertInstanceOf(\Carbon\Carbon::class, $metric->collected_at);

        $this->assertEquals(50.5, $metric->cpu_usage);
        $this->assertEquals(1.5, $metric->cpu_load_1);
        $this->assertEquals(8589934592, $metric->memory_total);
        $this->assertEquals(75.0, $metric->memory_usage_percent);
    }

    public function test_server_metric_for_time_range_scope(): void
    {
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
        
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($middleMetric));
        $this->assertFalse($results->contains($oldMetric));
        $this->assertFalse($results->contains($newMetric));
    }

    public function test_server_metric_recent_scope(): void
    {
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
        
        $this->assertTrue($results->contains($recentMetric));
        $this->assertFalse($results->contains($oldMetric));

        // Test recent scope with custom hours
        $results = ServerMetric::recent(10)->get();
        
        $this->assertFalse($results->contains($recentMetric));
        $this->assertFalse($results->contains($oldMetric));
    }

    public function test_server_metric_factory_generates_valid_data(): void
    {
        $metric = ServerMetric::factory()->create();

        // Test CPU metrics
        $this->assertGreaterThanOrEqual(0, $metric->cpu_usage);
        $this->assertLessThanOrEqual(100, $metric->cpu_usage);
        $this->assertGreaterThanOrEqual(0, $metric->cpu_load_1);
        $this->assertGreaterThanOrEqual(0, $metric->cpu_load_5);
        $this->assertGreaterThanOrEqual(0, $metric->cpu_load_15);

        // Test memory metrics
        $this->assertGreaterThanOrEqual(0, $metric->memory_total);
        $this->assertGreaterThanOrEqual(0, $metric->memory_used);
        $this->assertGreaterThanOrEqual(0, $metric->memory_available);
        $this->assertGreaterThanOrEqual(0, $metric->memory_usage_percent);
        $this->assertLessThanOrEqual(100, $metric->memory_usage_percent);

        // Test swap metrics
        $this->assertGreaterThanOrEqual(0, $metric->swap_total);
        $this->assertGreaterThanOrEqual(0, $metric->swap_used);
        $this->assertGreaterThanOrEqual(0, $metric->swap_usage_percent);
        $this->assertLessThanOrEqual(100, $metric->swap_usage_percent);

        // Test memory consistency
        $this->assertEquals(
            $metric->memory_total,
            $metric->memory_used + $metric->memory_available
        );

        $this->assertNotNull($metric->collected_at);
    }

    public function test_server_metric_factory_states(): void
    {
        $highCpuMetric = ServerMetric::factory()->highCpu()->create();
        $this->assertGreaterThanOrEqual(80, $highCpuMetric->cpu_usage);
        $this->assertGreaterThanOrEqual(4, $highCpuMetric->cpu_load_1);

        $highMemoryMetric = ServerMetric::factory()->highMemory()->create();
        $this->assertGreaterThanOrEqual(80, $highMemoryMetric->memory_usage_percent);

        $lowUsageMetric = ServerMetric::factory()->lowUsage()->create();
        $this->assertLessThanOrEqual(20, $lowUsageMetric->cpu_usage);
        $this->assertLessThanOrEqual(30, $lowUsageMetric->memory_usage_percent);

        $recentMetric = ServerMetric::factory()->recent()->create();
        $this->assertTrue($recentMetric->collected_at->greaterThan(now()->subHour()));

        $oldMetric = ServerMetric::factory()->old()->create();
        $this->assertTrue($oldMetric->collected_at->lessThan(now()->subDay()));
    }
}