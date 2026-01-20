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

test('displays enabled monitors', function () {
    $enabledMonitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Enabled Monitor',
        'is_enabled' => true,
    ]);

    $disabledMonitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Disabled Monitor',
        'is_enabled' => false,
    ]);

    Livewire::test(MonitoringWall::class)
        ->assertSee('Enabled Monitor')
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
        ->assertSee('Down Monitor')
        ->assertSee('down');
});

test('shows monitors without active anomaly as operational', function () {
    Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Healthy Monitor',
        'is_enabled' => true,
    ]);

    Livewire::test(MonitoringWall::class)
        ->assertSee('Healthy Monitor')
        ->assertSee('operational');
});

test('can filter monitors by selection', function () {
    $monitor1 = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Monitor One',
        'is_enabled' => true,
    ]);

    $monitor2 = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Monitor Two',
        'is_enabled' => true,
    ]);

    Livewire::test(MonitoringWall::class)
        ->call('updateSelectedMonitors', [$monitor1->id])
        ->assertSee('Monitor One')
        ->assertDontSee('Monitor Two');
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

    $component = Livewire::test(MonitoringWall::class);

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

    Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My Monitor',
        'is_enabled' => true,
    ]);

    Livewire::test(MonitoringWall::class)
        ->assertSee('My Monitor')
        ->assertDontSee('Other User Monitor');
});

test('shows average response time when checks exist', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Response Time Monitor',
        'is_enabled' => true,
    ]);

    Check::factory()->count(5)->create([
        'monitor_id' => $monitor->id,
        'response_time' => 100,
        'checked_at' => now(),
    ]);

    Livewire::test(MonitoringWall::class)
        ->assertSee('Response Time Monitor')
        ->assertSee('avg 100ms');
});

test('loads response times for sparkline display', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Sparkline Monitor',
        'is_enabled' => true,
    ]);

    Check::factory()->count(10)->create([
        'monitor_id' => $monitor->id,
        'response_time' => fake()->numberBetween(50, 200),
        'checked_at' => now()->subMinutes(fake()->numberBetween(1, 300)),
    ]);

    $component = Livewire::test(MonitoringWall::class);

    $displayMonitors = $component->viewData('this')['displayMonitors'];
    $monitorData = $displayMonitors->firstWhere('id', $monitor->id);

    expect($monitorData->response_times)->toBeArray()->toHaveCount(10);
});

test('shows last check information', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Last Check Monitor',
        'is_enabled' => true,
    ]);

    Check::factory()->create([
        'monitor_id' => $monitor->id,
        'response_time' => 150,
        'response_code' => 200,
        'checked_at' => now()->subMinutes(2),
    ]);

    Livewire::test(MonitoringWall::class)
        ->assertSee('Last Check Monitor')
        ->assertSee('150ms')
        ->assertSee('200');
});

test('provides downtime start time for active anomaly', function () {
    $monitor = Monitor::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Downtime Monitor',
        'is_enabled' => true,
    ]);

    $startedAt = now()->subMinutes(30);

    Anomaly::factory()->create([
        'monitor_id' => $monitor->id,
        'started_at' => $startedAt,
        'ended_at' => null,
    ]);

    $component = Livewire::test(MonitoringWall::class);

    $displayMonitors = $component->viewData('this')['displayMonitors'];
    $monitorData = $displayMonitors->firstWhere('id', $monitor->id);

    expect($monitorData->downtime_started_at)->not->toBeNull();
});
