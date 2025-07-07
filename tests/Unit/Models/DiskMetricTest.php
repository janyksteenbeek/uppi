<?php

namespace Tests\Unit\Models;

use App\Models\DiskMetric;
use App\Models\ServerMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiskMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_disk_metric_belongs_to_server_metric(): void
    {
        $serverMetric = ServerMetric::factory()->create();
        $diskMetric = DiskMetric::factory()->create([
            'server_metric_id' => $serverMetric->id
        ]);

        $this->assertInstanceOf(ServerMetric::class, $diskMetric->serverMetric);
        $this->assertEquals($serverMetric->id, $diskMetric->serverMetric->id);
    }

    public function test_disk_metric_formats_bytes_correctly(): void
    {
        $diskMetric = DiskMetric::factory()->create([
            'total_bytes' => 1024 * 1024 * 1024, // 1GB
            'used_bytes' => 512 * 1024 * 1024,   // 512MB
            'available_bytes' => 512 * 1024 * 1024, // 512MB
        ]);

        $this->assertEquals('1.00 GB', $diskMetric->formatted_total);
        $this->assertEquals('512.00 MB', $diskMetric->formatted_used);
        $this->assertEquals('512.00 MB', $diskMetric->formatted_available);
    }

    public function test_disk_metric_formats_small_bytes(): void
    {
        $diskMetric = DiskMetric::factory()->create([
            'total_bytes' => 1024,
            'used_bytes' => 512,
            'available_bytes' => 512,
        ]);

        $this->assertEquals('1.00 KB', $diskMetric->formatted_total);
        $this->assertEquals('512.00 B', $diskMetric->formatted_used);
        $this->assertEquals('512.00 B', $diskMetric->formatted_available);
    }

    public function test_disk_metric_formats_large_bytes(): void
    {
        $diskMetric = DiskMetric::factory()->create([
            'total_bytes' => 2 * 1024 * 1024 * 1024 * 1024, // 2TB
            'used_bytes' => 1024 * 1024 * 1024 * 1024,      // 1TB
            'available_bytes' => 1024 * 1024 * 1024 * 1024, // 1TB
        ]);

        $this->assertEquals('2.00 TB', $diskMetric->formatted_total);
        $this->assertEquals('1.00 TB', $diskMetric->formatted_used);
        $this->assertEquals('1.00 TB', $diskMetric->formatted_available);
    }

    public function test_disk_metric_handles_zero_bytes(): void
    {
        $diskMetric = DiskMetric::factory()->create([
            'total_bytes' => 0,
            'used_bytes' => 0,
            'available_bytes' => 0,
        ]);

        $this->assertEquals('0 B', $diskMetric->formatted_total);
        $this->assertEquals('0 B', $diskMetric->formatted_used);
        $this->assertEquals('0 B', $diskMetric->formatted_available);
    }

    public function test_disk_metric_casts_attributes_correctly(): void
    {
        $diskMetric = DiskMetric::factory()->create([
            'total_bytes' => '1073741824',
            'used_bytes' => '536870912',
            'available_bytes' => '536870912',
            'usage_percent' => '50.0',
        ]);

        $this->assertIsInt($diskMetric->total_bytes);
        $this->assertIsInt($diskMetric->used_bytes);
        $this->assertIsInt($diskMetric->available_bytes);
        $this->assertIsFloat($diskMetric->usage_percent);

        $this->assertEquals(1073741824, $diskMetric->total_bytes);
        $this->assertEquals(536870912, $diskMetric->used_bytes);
        $this->assertEquals(50.0, $diskMetric->usage_percent);
    }

    public function test_disk_metric_factory_generates_valid_data(): void
    {
        $diskMetric = DiskMetric::factory()->create();

        $this->assertNotNull($diskMetric->mount_point);
        $this->assertGreaterThanOrEqual(0, $diskMetric->total_bytes);
        $this->assertGreaterThanOrEqual(0, $diskMetric->used_bytes);
        $this->assertGreaterThanOrEqual(0, $diskMetric->available_bytes);
        $this->assertGreaterThanOrEqual(0, $diskMetric->usage_percent);
        $this->assertLessThanOrEqual(100, $diskMetric->usage_percent);
        
        // Verify bytes add up correctly
        $this->assertEquals(
            $diskMetric->total_bytes,
            $diskMetric->used_bytes + $diskMetric->available_bytes
        );
    }
}