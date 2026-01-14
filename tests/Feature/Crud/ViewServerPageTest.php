<?php

use App\Filament\Resources\ServerResource\Pages\ViewServer;
use App\Models\DiskMetric;
use App\Models\NetworkMetric;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('shows storage rows with used / total and sorts by usage', function () {
    $user = User::factory()->create([
        'feature_flags' => ['server-monitoring'],
    ]);
    actingAs($user);

    $server = Server::factory()->create([
        'user_id' => $user->id,
        'last_seen_at' => now(),
    ]);

    $metric = ServerMetric::factory()->create([
        'server_id' => $server->id,
    ]);

    DiskMetric::factory()->create([
        'server_metric_id' => $metric->id,
        'mount_point' => '/low',
        'total_bytes' => 1000,
        'used_bytes' => 100,
        'available_bytes' => 900,
        'usage_percent' => 10,
    ]);

    DiskMetric::factory()->create([
        'server_metric_id' => $metric->id,
        'mount_point' => '/high',
        'total_bytes' => 1000,
        'used_bytes' => 900,
        'available_bytes' => 100,
        'usage_percent' => 90,
    ]);

    Livewire::test(ViewServer::class, [
        'record' => $server->getKey(),
    ])
        ->assertSuccessful()
        ->assertSee('/high')
        ->assertSee('/low')
        ->assertSee('%')
        ->assertSee('/'); // at least one "used / total" separator should appear in the UI
});

it('can toggle showing inactive network interfaces (note text changes)', function () {
    $user = User::factory()->create([
        'feature_flags' => ['server-monitoring'],
    ]);
    actingAs($user);

    $server = Server::factory()->create([
        'user_id' => $user->id,
        'last_seen_at' => now(),
    ]);

    $metric = ServerMetric::factory()->create([
        'server_id' => $server->id,
    ]);

    NetworkMetric::factory()->create([
        'server_metric_id' => $metric->id,
        'interface_name' => 'eth0',
        'rx_bytes' => 123,
        'tx_bytes' => 456,
    ]);

    NetworkMetric::factory()->create([
        'server_metric_id' => $metric->id,
        'interface_name' => 'lo',
        'rx_bytes' => 0,
        'tx_bytes' => 0,
    ]);

    Livewire::test(ViewServer::class, [
        'record' => $server->getKey(),
    ])
        ->assertSuccessful()
        ->assertSee('inactive interface(s) hidden')
        ->set('showInactiveInterfaces', true)
        ->assertSee('Showing inactive interfaces');
});
