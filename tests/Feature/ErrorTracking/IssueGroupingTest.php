<?php

use App\Jobs\ErrorTracking\ProcessSentryEnvelopeJob;
use App\Models\ErrorTracking\Event;
use App\Models\ErrorTracking\Issue;
use App\Models\ErrorTracking\Project;
use App\Services\ErrorTracking\Envelope\EnvelopeParser;
use App\Services\ErrorTracking\FingerprintGenerator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function buildEventEnvelope(Project $project, array $event): string
{
    $eventId = Str::lower(Str::random(32));
    $event = array_merge(['event_id' => $eventId], $event);

    $payload = json_encode($event);

    $envelopeHeader = json_encode([
        'event_id' => $eventId,
        'dsn' => "https://{$project->public_key}@uppi.test/{$project->internal_id}",
    ]);
    $itemHeader = json_encode(['type' => 'event', 'content_type' => 'application/json', 'length' => strlen($payload)]);

    return $envelopeHeader."\n".$itemHeader."\n".$payload;
}

function ingestEvent(Project $project, array $event): void
{
    $body = buildEventEnvelope($project, $event);
    (new ProcessSentryEnvelopeJob($project->id, $body))
        ->handle(app(EnvelopeParser::class), app(FingerprintGenerator::class));
}

beforeEach(function () {
    Queue::fake();
});

it('groups identical exceptions into one issue and increments the counter', function () {
    $project = Project::factory()->create();

    $sample = [
        'platform' => 'php',
        'level' => 'error',
        'timestamp' => now()->getTimestamp(),
        'exception' => [
            'values' => [[
                'type' => 'RuntimeException',
                'value' => 'something broke',
                'stacktrace' => [
                    'frames' => [[
                        'filename' => 'app/Foo.php',
                        'function' => 'doWork',
                        'lineno' => 11,
                        'in_app' => true,
                    ]],
                ],
            ]],
        ],
    ];

    ingestEvent($project, $sample);
    ingestEvent($project, $sample);
    ingestEvent($project, $sample);

    expect(Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->count())->toBe(1);
    expect(Event::query()->withoutGlobalScope('user')->where('project_id', $project->id)->count())->toBe(3);

    $issue = Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->first();
    expect($issue->times_seen)->toBe(3);
    expect($issue->title)->toContain('RuntimeException');
});

it('creates separate issues for different exception types', function () {
    $project = Project::factory()->create();

    ingestEvent($project, [
        'platform' => 'php',
        'level' => 'error',
        'exception' => ['values' => [['type' => 'A', 'value' => 'x']]],
    ]);
    ingestEvent($project, [
        'platform' => 'php',
        'level' => 'error',
        'exception' => ['values' => [['type' => 'B', 'value' => 'x']]],
    ]);

    expect(Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->count())->toBe(2);
});

it('reopens a resolved issue when a new event arrives', function () {
    $project = Project::factory()->create();

    ingestEvent($project, [
        'platform' => 'php',
        'level' => 'error',
        'exception' => ['values' => [['type' => 'A', 'value' => 'x']]],
    ]);

    $issue = Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->firstOrFail();
    $issue->update(['status' => 'resolved', 'resolved_at' => now()->subHour()]);

    ingestEvent($project, [
        'platform' => 'php',
        'level' => 'error',
        'exception' => ['values' => [['type' => 'A', 'value' => 'x']]],
    ]);

    $issue->refresh();
    expect($issue->status->value)->toBe('open');
    expect($issue->resolved_at)->toBeNull();
});
