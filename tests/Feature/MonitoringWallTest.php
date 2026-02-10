<?php

use App\Livewire\MonitoringWall;
use App\Models\Anomaly;
use App\Models\Check;
use App\Models\Monitor;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('requires authentication to access monitoring wall', function () {
    auth()->logout();

    $this->get(route('monitoring-wall'))
        ->assertRedirect();
});

test('can view monitoring wall page', function () {
    $this->get(route('monitoring-wall'))
        ->assertSuccessful()
        ->assertSeeLivewire(MonitoringWall::class);
});

test('shows empty state by default with no selection', function () {
    Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My Monitor',
        'is_enabled' => true,
    ]);

    Livewire::test(MonitoringWall::class)
        ->assertSee('no monitors selected')
        ->assertDontSee('My Monitor');
});

test('displays selected monitors', function () {
    $monitor1 = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Monitor One',
        'is_enabled' => true,
    ]);

    Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Monitor Two',
        'is_enabled' => true,
    ]);

    Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$monitor1->id])
        ->assertSee('Monitor One')
        ->assertDontSee('Monitor Two');
});

test('does not display disabled monitors even if selected', function () {
    $disabledMonitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Disabled Monitor',
        'is_enabled' => false,
    ]);

    Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$disabledMonitor->id])
        ->assertDontSee('Disabled Monitor');
});

test('shows monitors with active anomaly as down', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Down Monitor',
        'is_enabled' => true,
    ]);

    Anomaly::factory()->create([
        'monitor_id' => $monitor->id,
        'started_at' => now(),
        'ended_at' => null,
    ]);

    Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$monitor->id])
        ->assertSee('Down Monitor')
        ->assertSee('down');
});

test('shows monitors without active anomaly as operational', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Healthy Monitor',
        'is_enabled' => true,
    ]);

    Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$monitor->id])
        ->assertSee('Healthy Monitor')
        ->assertSee('operational');
});

test('sorts monitors with down monitors first', function () {
    $healthyMonitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'AAA Healthy Monitor',
        'is_enabled' => true,
    ]);

    $downMonitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'ZZZ Down Monitor',
        'is_enabled' => true,
    ]);

    Anomaly::factory()->create([
        'monitor_id' => $downMonitor->id,
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $component = Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$healthyMonitor->id, $downMonitor->id]);

    $displayMonitors = $component->viewData('this')['displayMonitors'];

    expect($displayMonitors->first()->id)->toBe($downMonitor->id);
});

test('does not show other users monitors', function () {
    $otherUser = User::factory()->create();

    Monitor::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Monitor',
        'is_enabled' => true,
    ]);

    $myMonitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My Monitor',
        'is_enabled' => true,
    ]);

    Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$myMonitor->id])
        ->assertSee('My Monitor')
        ->assertDontSee('Other User Monitor');
});

test('provides downtime start time for active anomaly', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Downtime Monitor',
        'is_enabled' => true,
    ]);

    Anomaly::factory()->create([
        'monitor_id' => $monitor->id,
        'started_at' => now()->subMinutes(30),
        'ended_at' => null,
    ]);

    $component = Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$monitor->id]);

    $displayMonitors = $component->viewData('this')['displayMonitors'];
    $monitorData = $displayMonitors->firstWhere('id', $monitor->id);

    expect($monitorData->downtime_started_at)->not->toBeNull();
});

test('shows last check response time', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Response Monitor',
        'is_enabled' => true,
    ]);

    Check::factory()->create([
        'monitor_id' => $monitor->id,
        'response_time' => 142,
        'response_code' => 200,
        'checked_at' => now(),
    ]);

    Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$monitor->id])
        ->assertSee('142ms')
        ->assertSee('200');
});

test('lists all enabled monitors in options', function () {
    Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Available Monitor',
        'is_enabled' => true,
    ]);

    Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Disabled Monitor',
        'is_enabled' => false,
    ]);

    $component = Livewire::test(MonitoringWall::class);
    $options = $component->viewData('this')['monitorOptions'];

    expect($options)->toHaveKey('Available Monitor')
        ->not->toHaveKey('Disabled Monitor');
});
