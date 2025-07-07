<?php

use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $user->id]);

    expect($server->user)
        ->toBeInstanceOf(User::class)
        ->and($server->user->id)->toBe($user->id);
});

it('has many metrics', function () {
    $server = Server::factory()->create();
    $metric1 = ServerMetric::factory()->create(['server_id' => $server->id]);
    $metric2 = ServerMetric::factory()->create(['server_id' => $server->id]);

    expect($server->metrics)
        ->toHaveCount(2)
        ->and($server->metrics->contains($metric1))->toBeTrue()
        ->and($server->metrics->contains($metric2))->toBeTrue();
});

it('automatically generates secret on creation', function () {
    $server = Server::factory()->create(['secret' => null]);

    expect($server->secret)
        ->not->toBeNull()
        ->and(strlen($server->secret))->toBe(32);
});

it('does not override provided secret', function () {
    $customSecret = 'my-custom-secret-key';
    $server = Server::factory()->create(['secret' => $customSecret]);

    expect($server->secret)->toBe($customSecret);
});

it('is online when last seen recently', function () {
    $server = Server::factory()->create([
        'last_seen_at' => now()->subMinutes(2)
    ]);

    expect($server->isOnline())->toBeTrue();
});

it('is offline when last seen too long ago', function () {
    $server = Server::factory()->create([
        'last_seen_at' => now()->subMinutes(10)
    ]);

    expect($server->isOnline())->toBeFalse();
});

it('is offline when never seen', function () {
    $server = Server::factory()->create([
        'last_seen_at' => null
    ]);

    expect($server->isOnline())->toBeFalse();
});

it('can generate new secret', function () {
    $server = Server::factory()->create();
    $originalSecret = $server->secret;

    $newSecret = $server->generateNewSecret();

    expect($newSecret)
        ->not->toBe($originalSecret)
        ->and(strlen($newSecret))->toBe(32)
        ->and($server->fresh()->secret)->toBe($newSecret);
});

it('has recent metrics scope', function () {
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

    expect($recentMetrics)
        ->toHaveCount(1)
        ->and($recentMetrics->contains($recentMetric))->toBeTrue();
});

it('returns latest metric', function () {
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

    expect($result)
        ->not->toBeNull()
        ->and($result->id)->toBe($latestMetric->id);
});

it('hides secret in array', function () {
    $server = Server::factory()->create();
    $array = $server->toArray();

    expect($array)->not->toHaveKey('secret');
});

it('is scoped to authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $server1 = Server::factory()->create(['user_id' => $user1->id]);
    $server2 = Server::factory()->create(['user_id' => $user2->id]);

    // Test that servers are scoped to the authenticated user
    $this->actingAs($user1);
    
    $servers = Server::all();
    
    expect($servers)
        ->toHaveCount(1)
        ->and($servers->contains($server1))->toBeTrue()
        ->and($servers->contains($server2))->toBeFalse();
});