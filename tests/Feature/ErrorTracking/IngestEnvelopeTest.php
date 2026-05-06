<?php

use App\Jobs\ErrorTracking\ProcessSentryEnvelopeJob;
use App\Models\ErrorTracking\Project;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    Queue::fake();
});

function buildEnvelope(string $publicKey, int $internalId, array $eventOverrides = []): string
{
    $eventId = Str::lower(Str::random(32));

    $event = array_merge([
        'event_id' => $eventId,
        'platform' => 'php',
        'level' => 'error',
        'timestamp' => now()->getTimestamp(),
        'exception' => [
            'values' => [[
                'type' => 'RuntimeException',
                'value' => 'boom',
                'stacktrace' => [
                    'frames' => [[
                        'filename' => 'app/Http/Controllers/Foo.php',
                        'function' => 'index',
                        'lineno' => 10,
                        'in_app' => true,
                    ]],
                ],
            ]],
        ],
    ], $eventOverrides);

    $envelopeHeader = json_encode([
        'event_id' => $eventId,
        'dsn' => "https://{$publicKey}@uppi.test/{$internalId}",
        'sent_at' => now()->toIso8601String(),
    ]);

    $payload = json_encode($event);
    $itemHeader = json_encode([
        'type' => 'event',
        'content_type' => 'application/json',
        'length' => strlen($payload),
    ]);

    return $envelopeHeader."\n".$itemHeader."\n".$payload;
}

it('accepts a valid envelope and dispatches a processing job', function () {
    $project = Project::factory()->create();

    $body = buildEnvelope($project->public_key, $project->internal_id);

    $response = $this->call(
        method: 'POST',
        uri: "/api/{$project->internal_id}/envelope",
        server: ['CONTENT_TYPE' => 'application/x-sentry-envelope'],
        content: $body,
    );

    $response->assertOk();
    expect($response->json('id'))->toBeString()->toHaveLength(32);

    Queue::assertPushed(ProcessSentryEnvelopeJob::class, fn ($job) => $job->projectId === $project->id);
});

it('rejects envelopes with an unknown public key', function () {
    $project = Project::factory()->create();

    $body = buildEnvelope('wrongkeywrongkeywrongkeywrongkey', $project->internal_id);

    $response = $this->call(
        method: 'POST',
        uri: "/api/{$project->internal_id}/envelope",
        server: ['CONTENT_TYPE' => 'application/x-sentry-envelope'],
        content: $body,
    );

    $response->assertStatus(401);
    Queue::assertNothingPushed();
});

it('rejects envelopes for unknown projects', function () {
    $response = $this->call(
        method: 'POST',
        uri: '/api/999999/envelope',
        server: ['CONTENT_TYPE' => 'application/x-sentry-envelope'],
        content: '{}',
    );

    $response->assertStatus(404);
});

it('decompresses gzip payloads transparently', function () {
    $project = Project::factory()->create();

    $body = buildEnvelope($project->public_key, $project->internal_id);
    $gzipped = gzencode($body);

    $response = $this->call(
        method: 'POST',
        uri: "/api/{$project->internal_id}/envelope",
        server: [
            'CONTENT_TYPE' => 'application/x-sentry-envelope',
            'HTTP_CONTENT_ENCODING' => 'gzip',
        ],
        content: $gzipped,
    );

    $response->assertOk();
    Queue::assertPushed(ProcessSentryEnvelopeJob::class);
});

it('reads the public key from the X-Sentry-Auth header when DSN is missing', function () {
    $project = Project::factory()->create();

    $eventId = Str::lower(Str::random(32));
    $payload = json_encode([
        'event_id' => $eventId,
        'platform' => 'php',
        'level' => 'error',
        'timestamp' => now()->getTimestamp(),
        'message' => 'header-auth',
    ]);
    $envelopeHeader = json_encode(['event_id' => $eventId]);
    $itemHeader = json_encode(['type' => 'event', 'content_type' => 'application/json']);
    $body = $envelopeHeader."\n".$itemHeader."\n".$payload;

    $response = $this->call(
        method: 'POST',
        uri: "/api/{$project->internal_id}/envelope",
        server: [
            'CONTENT_TYPE' => 'application/x-sentry-envelope',
            'HTTP_X_SENTRY_AUTH' => "Sentry sentry_version=7, sentry_key={$project->public_key}, sentry_client=sentry.php/4.10",
        ],
        content: $body,
    );

    $response->assertOk();
    Queue::assertPushed(ProcessSentryEnvelopeJob::class);
});
