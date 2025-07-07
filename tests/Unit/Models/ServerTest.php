<?php

namespace Tests\Unit\Models;

use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $server->user);
        $this->assertEquals($user->id, $server->user->id);
    }

    public function test_server_has_many_metrics(): void
    {
        $server = Server::factory()->create();
        $metric1 = ServerMetric::factory()->create(['server_id' => $server->id]);
        $metric2 = ServerMetric::factory()->create(['server_id' => $server->id]);

        $this->assertCount(2, $server->metrics);
        $this->assertTrue($server->metrics->contains($metric1));
        $this->assertTrue($server->metrics->contains($metric2));
    }

    public function test_server_automatically_generates_secret_on_creation(): void
    {
        $server = Server::factory()->create(['secret' => null]);

        $this->assertNotNull($server->secret);
        $this->assertEquals(32, strlen($server->secret));
    }

    public function test_server_does_not_override_provided_secret(): void
    {
        $customSecret = 'my-custom-secret-key';
        $server = Server::factory()->create(['secret' => $customSecret]);

        $this->assertEquals($customSecret, $server->secret);
    }

    public function test_server_is_online_when_last_seen_recently(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(2)
        ]);

        $this->assertTrue($server->isOnline());
    }

    public function test_server_is_offline_when_last_seen_too_long_ago(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => now()->subMinutes(10)
        ]);

        $this->assertFalse($server->isOnline());
    }

    public function test_server_is_offline_when_never_seen(): void
    {
        $server = Server::factory()->create([
            'last_seen_at' => null
        ]);

        $this->assertFalse($server->isOnline());
    }

    public function test_server_can_generate_new_secret(): void
    {
        $server = Server::factory()->create();
        $originalSecret = $server->secret;

        $newSecret = $server->generateNewSecret();

        $this->assertNotEquals($originalSecret, $newSecret);
        $this->assertEquals(32, strlen($newSecret));
        $this->assertEquals($newSecret, $server->fresh()->secret);
    }

    public function test_server_recent_metrics_scope(): void
    {
        $server = Server::factory()->create();
        
        // Create old metrics (8 days ago)
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'created_at' => now()->subDays(8)
        ]);
        
        // Create recent metrics (3 days ago)
        $recentMetric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'created_at' => now()->subDays(3)
        ]);

        $recentMetrics = $server->recentMetrics()->get();

        $this->assertCount(1, $recentMetrics);
        $this->assertTrue($recentMetrics->contains($recentMetric));
    }

    public function test_server_latest_metric(): void
    {
        $server = Server::factory()->create();
        
        $oldMetric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'created_at' => now()->subHours(2)
        ]);
        
        $latestMetric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'created_at' => now()
        ]);

        $result = $server->latestMetric();

        $this->assertNotNull($result);
        $this->assertEquals($latestMetric->id, $result->id);
    }

    public function test_server_hides_secret_in_array(): void
    {
        $server = Server::factory()->create();
        $array = $server->toArray();

        $this->assertArrayNotHasKey('secret', $array);
    }

    public function test_server_scoped_to_authenticated_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $server1 = Server::factory()->create(['user_id' => $user1->id]);
        $server2 = Server::factory()->create(['user_id' => $user2->id]);

        // Test that servers are scoped to the authenticated user
        $this->actingAs($user1);
        
        $servers = Server::all();
        
        $this->assertCount(1, $servers);
        $this->assertTrue($servers->contains($server1));
        $this->assertFalse($servers->contains($server2));
    }
}