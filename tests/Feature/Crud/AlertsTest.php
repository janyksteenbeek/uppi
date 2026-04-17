<?php

use App\Enums\Alerts\AlertType;
use App\Filament\Resources\AlertResource;
use App\Filament\Resources\AlertResource\Pages\ManageAlerts;
use App\Models\Alert;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can view alerts page', function () {
    $this->get(AlertResource::getUrl())
        ->assertSuccessful();
});

test('can create an email alert through Uppi UI', function () {
    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => 'Test Email Alert',
            'type' => AlertType::EMAIL->value,
            'destination' => 'test@example.com',
            'is_enabled' => true,
        ]);

    assertDatabaseHas('alerts', [
        'name' => 'Test Email Alert',
        'type' => AlertType::EMAIL->value,
        'destination' => 'test@example.com',
        'is_enabled' => true,
        'user_id' => $this->user->id,
    ]);
});

test('can create a slack alert through Uppi UI', function () {
    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => 'Test Slack Alert',
            'type' => AlertType::SLACK->value,
            'destination' => '#monitoring',
            'is_enabled' => true,
            'config' => [
                'slack_token' => 'xoxb-test-token',
            ],
        ]);

    $alert = Alert::first();
    expect($alert)
        ->name->toBe('Test Slack Alert')
        ->type->toBe(AlertType::SLACK)
        ->destination->toBe('#monitoring')
        ->is_enabled->toBeTrue();

    expect($alert->config)
        ->toHaveKey('slack_token')
        ->and($alert->config['slack_token'])->toBe('xoxb-test-token');
});

test('can create a bird alert through Uppi UI', function () {
    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => 'Test Bird Alert',
            'type' => AlertType::BIRD->value,
            'destination' => '+31612345678',
            'is_enabled' => true,
            'config' => [
                'bird_api_key' => 'test-api-key',
                'bird_workspace_id' => 'test-workspace',
                'bird_channel_id' => 'test-channel',
            ],
        ]);

    $alert = Alert::first();
    expect($alert)
        ->name->toBe('Test Bird Alert')
        ->type->toBe(AlertType::BIRD)
        ->destination->toBe('+31612345678');

    expect($alert->config)
        ->toHaveKey('bird_api_key')
        ->toHaveKey('bird_workspace_id')
        ->toHaveKey('bird_channel_id');
});

test('can create a pushover alert through Uppi UI', function () {
    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => 'Test Pushover Alert',
            'type' => AlertType::PUSHOVER->value,
            'destination' => 'user-key',
            'is_enabled' => true,
            'config' => [
                'pushover_api_token' => 'app-token',
            ],
        ]);

    $alert = Alert::first();
    expect($alert)
        ->name->toBe('Test Pushover Alert')
        ->type->toBe(AlertType::PUSHOVER)
        ->destination->toBe('user-key')
        ->is_enabled->toBeTrue();

    expect($alert->config)
        ->toHaveKey('pushover_api_token')
        ->and($alert->config['pushover_api_token'])->toBe('app-token');
});

test('cannot create an expo alert through Uppi UI', function () {
    $test = Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => 'Test Expo Alert',
            'type' => AlertType::EXPO->value,
            'destination' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'is_enabled' => true,
        ]);

    $test->assertHasActionErrors(['uppi_app_info']);

    assertDatabaseMissing('alerts', [
        'name' => 'Test Expo Alert',
        'type' => AlertType::EXPO->value,
        'destination' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
        'is_enabled' => true,
        'user_id' => $this->user->id,
    ]);
});

test('validates required fields when creating an alert', function () {
    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => '',
            'type' => AlertType::EMAIL->value,
            'destination' => '',
        ])
        ->assertHasActionErrors(['name', 'destination']);
});

test('validates email format for email alerts', function () {
    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => 'Test Alert',
            'type' => AlertType::EMAIL->value,
            'destination' => 'not-an-email',
        ])
        ->assertHasActionErrors(['destination']);
});

test('requires slack token for slack alerts', function () {
    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => 'Test Slack Alert',
            'type' => AlertType::SLACK->value,
            'destination' => '#monitoring',
            'config' => [],
        ])
        ->assertHasActionErrors(['config.slack_token']);
});

test('cannot access someone else\'s alerts', function () {
    $otherUser = User::factory()->create();
    $alert = Alert::factory()->email()->create([
        'user_id' => $otherUser->id,
    ]);

    Livewire::test(ManageAlerts::class)
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$alert]);
});
