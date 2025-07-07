<?php

namespace Tests\Unit\Models;

use App\Models\NetworkMetric;
use App\Models\ServerMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_network_metric_belongs_to_server_metric(): void
    {
        $serverMetric = ServerMetric::factory()->create();
        $networkMetric = NetworkMetric::factory()->create([
            'server_metric_id' => $serverMetric->id
        ]);

        $this->assertInstanceOf(ServerMetric::class, $networkMetric->serverMetric);
        $this->assertEquals($serverMetric->id, $networkMetric->serverMetric->id);
    }

    public function test_network_metric_formats_bytes_correctly(): void
    {
        $networkMetric = NetworkMetric::factory()->create([
            'rx_bytes' => 1024 * 1024 * 1024, // 1GB
            'tx_bytes' => 512 * 1024 * 1024,  // 512MB
        ]);

        $this->assertEquals('1.00 GB', $networkMetric->formatted_rx_bytes);
        $this->assertEquals('512.00 MB', $networkMetric->formatted_tx_bytes);
    }

    public function test_network_metric_calculates_total_bytes(): void
    {
        $networkMetric = NetworkMetric::factory()->create([
            'rx_bytes' => 1000,
            'tx_bytes' => 500,
        ]);

        $this->assertEquals(1500, $networkMetric->total_bytes);
        $this->assertEquals('1.46 KB', $networkMetric->formatted_total_bytes);
    }

    public function test_network_metric_handles_zero_bytes(): void
    {
        $networkMetric = NetworkMetric::factory()->create([
            'rx_bytes' => 0,
            'tx_bytes' => 0,
        ]);

        $this->assertEquals('0 B', $networkMetric->formatted_rx_bytes);
        $this->assertEquals('0 B', $networkMetric->formatted_tx_bytes);
        $this->assertEquals(0, $networkMetric->total_bytes);
        $this->assertEquals('0 B', $networkMetric->formatted_total_bytes);
    }

    public function test_network_metric_formats_large_bytes(): void
    {
        $networkMetric = NetworkMetric::factory()->create([
            'rx_bytes' => 2 * 1024 * 1024 * 1024 * 1024, // 2TB
            'tx_bytes' => 1024 * 1024 * 1024 * 1024,      // 1TB
        ]);

        $this->assertEquals('2.00 TB', $networkMetric->formatted_rx_bytes);
        $this->assertEquals('1.00 TB', $networkMetric->formatted_tx_bytes);
        $this->assertEquals('3.00 TB', $networkMetric->formatted_total_bytes);
    }

    public function test_network_metric_casts_attributes_correctly(): void
    {
        $networkMetric = NetworkMetric::factory()->create([
            'rx_bytes' => '1073741824',
            'tx_bytes' => '536870912',
            'rx_packets' => '1000',
            'tx_packets' => '500',
            'rx_errors' => '5',
            'tx_errors' => '3',
        ]);

        $this->assertIsInt($networkMetric->rx_bytes);
        $this->assertIsInt($networkMetric->tx_bytes);
        $this->assertIsInt($networkMetric->rx_packets);
        $this->assertIsInt($networkMetric->tx_packets);
        $this->assertIsInt($networkMetric->rx_errors);
        $this->assertIsInt($networkMetric->tx_errors);

        $this->assertEquals(1073741824, $networkMetric->rx_bytes);
        $this->assertEquals(536870912, $networkMetric->tx_bytes);
        $this->assertEquals(1000, $networkMetric->rx_packets);
        $this->assertEquals(500, $networkMetric->tx_packets);
        $this->assertEquals(5, $networkMetric->rx_errors);
        $this->assertEquals(3, $networkMetric->tx_errors);
    }

    public function test_network_metric_factory_generates_valid_data(): void
    {
        $networkMetric = NetworkMetric::factory()->create();

        $this->assertNotNull($networkMetric->interface_name);
        $this->assertGreaterThanOrEqual(0, $networkMetric->rx_bytes);
        $this->assertGreaterThanOrEqual(0, $networkMetric->tx_bytes);
        
        // Optional fields can be null
        if ($networkMetric->rx_packets !== null) {
            $this->assertGreaterThanOrEqual(0, $networkMetric->rx_packets);
        }
        if ($networkMetric->tx_packets !== null) {
            $this->assertGreaterThanOrEqual(0, $networkMetric->tx_packets);
        }
        if ($networkMetric->rx_errors !== null) {
            $this->assertGreaterThanOrEqual(0, $networkMetric->rx_errors);
        }
        if ($networkMetric->tx_errors !== null) {
            $this->assertGreaterThanOrEqual(0, $networkMetric->tx_errors);
        }
    }

    public function test_network_metric_factory_states(): void
    {
        $ethernetMetric = NetworkMetric::factory()->ethernet()->create();
        $this->assertContains($ethernetMetric->interface_name, ['eth0', 'eth1', 'ens33', 'enp0s3']);

        $wifiMetric = NetworkMetric::factory()->wifi()->create();
        $this->assertContains($wifiMetric->interface_name, ['wlan0', 'wlan1', 'wlp2s0']);

        $loopbackMetric = NetworkMetric::factory()->loopback()->create();
        $this->assertEquals('lo', $loopbackMetric->interface_name);
        $this->assertEquals(0, $loopbackMetric->rx_errors);
        $this->assertEquals(0, $loopbackMetric->tx_errors);

        $highTrafficMetric = NetworkMetric::factory()->highTraffic()->create();
        $this->assertGreaterThanOrEqual(50 * 1024 * 1024 * 1024, $highTrafficMetric->rx_bytes);
        $this->assertGreaterThanOrEqual(50 * 1024 * 1024 * 1024, $highTrafficMetric->tx_bytes);

        $lowTrafficMetric = NetworkMetric::factory()->lowTraffic()->create();
        $this->assertLessThanOrEqual(1024 * 1024 * 1024, $lowTrafficMetric->rx_bytes);
        $this->assertLessThanOrEqual(1024 * 1024 * 1024, $lowTrafficMetric->tx_bytes);

        $errorMetric = NetworkMetric::factory()->withErrors()->create();
        $this->assertGreaterThanOrEqual(10, $errorMetric->rx_errors);
        $this->assertGreaterThanOrEqual(10, $errorMetric->tx_errors);

        $cleanMetric = NetworkMetric::factory()->clean()->create();
        $this->assertEquals(0, $cleanMetric->rx_errors);
        $this->assertEquals(0, $cleanMetric->tx_errors);
    }
}