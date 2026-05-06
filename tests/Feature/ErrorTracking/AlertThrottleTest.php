<?php

use App\Enums\Alerts\AlertType;
use App\Enums\ErrorTracking\IssueAlertCondition;
use App\Jobs\ErrorTracking\EvaluateIssueAlertRulesJob;
use App\Jobs\ErrorTracking\Notifications\SendIssueAlertNotificationJob;
use App\Jobs\ErrorTracking\ProcessSentryEnvelopeJob;
use App\Models\Alert;
use App\Models\ErrorTracking\Event;
use App\Models\ErrorTracking\Issue;
use App\Models\ErrorTracking\IssueAnomaly;
use App\Models\ErrorTracking\IssueAnomalyRule;
use App\Models\ErrorTracking\Project;
use App\Models\User;
use App\Notifications\ErrorTracking\IssueDetectedNotification;
use App\Services\ErrorTracking\Envelope\EnvelopeParser;
use App\Services\ErrorTracking\FingerprintGenerator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function envelopeFor(Project $project): string
{
    $eventId = Str::lower(Str::random(32));
    $payload = json_encode([
        'event_id' => $eventId,
        'platform' => 'php',
        'level' => 'error',
        'timestamp' => now()->getTimestamp(),
        'exception' => ['values' => [[
            'type' => 'RuntimeException',
            'value' => 'boom',
            'stacktrace' => ['frames' => [[
                'filename' => 'app/Foo.php',
                'function' => 'work',
                'lineno' => 1,
                'in_app' => true,
            ]]],
        ]]],
    ]);
    $envelopeHeader = json_encode([
        'event_id' => $eventId,
        'dsn' => "https://{$project->public_key}@uppi.test/{$project->internal_id}",
    ]);
    $itemHeader = json_encode(['type' => 'event', 'content_type' => 'application/json']);

    return $envelopeHeader."\n".$itemHeader."\n".$payload;
}

function ingest(Project $project): void
{
    (new ProcessSentryEnvelopeJob($project->id, envelopeFor($project)))
        ->handle(app(EnvelopeParser::class), app(FingerprintGenerator::class));
}

it('honors the throttle window so repeated events do not spam notifications', function () {
    Notification::fake();
    Bus::fake([SendIssueAlertNotificationJob::class]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $alert = Alert::factory()->create(['user_id' => $user->id, 'type' => AlertType::EMAIL, 'destination' => 'a@b.test']);
    $rule = IssueAnomalyRule::factory()
        ->for($user)
        ->for($project)
        ->state(['condition_type' => IssueAlertCondition::FIRST_SEEN, 'throttle_window_minutes' => 60])
        ->create();
    $rule->alerts()->attach($alert->id);

    ingest($project);

    $issue = Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->firstOrFail();
    $event = Event::query()->withoutGlobalScope('user')->where('issue_id', $issue->id)->latest()->firstOrFail();

    (new EvaluateIssueAlertRulesJob($issue->id, $event->id, true, false))->handle();
    (new EvaluateIssueAlertRulesJob($issue->id, $event->id, false, false))->handle();
    (new EvaluateIssueAlertRulesJob($issue->id, $event->id, false, false))->handle();

    expect(IssueAnomaly::query()->where('rule_id', $rule->id)->count())->toBe(1);
    Bus::assertDispatchedTimes(SendIssueAlertNotificationJob::class, 1);
});

it('triggers when the threshold is met inside the window', function () {
    Notification::fake();
    Bus::fake([SendIssueAlertNotificationJob::class]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $alert = Alert::factory()->create(['user_id' => $user->id, 'type' => AlertType::EMAIL, 'destination' => 'a@b.test']);
    $rule = IssueAnomalyRule::factory()
        ->for($user)
        ->for($project)
        ->threshold(count: 3, windowMinutes: 5)
        ->state(['throttle_window_minutes' => 60])
        ->create();
    $rule->alerts()->attach($alert->id);

    ingest($project);
    ingest($project);
    ingest($project);

    $issue = Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->firstOrFail();
    expect($issue->times_seen)->toBe(3);

    $event = Event::query()->withoutGlobalScope('user')->where('issue_id', $issue->id)->latest()->firstOrFail();

    (new EvaluateIssueAlertRulesJob($issue->id, $event->id, false, false))->handle();

    expect(IssueAnomaly::query()->where('rule_id', $rule->id)->count())->toBe(1);
    Bus::assertDispatchedTimes(SendIssueAlertNotificationJob::class, 1);
});

it('triggers regression rules when a resolved issue receives a new event', function () {
    Notification::fake();
    Bus::fake([SendIssueAlertNotificationJob::class]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $alert = Alert::factory()->create(['user_id' => $user->id, 'type' => AlertType::EMAIL, 'destination' => 'r@b.test']);
    $rule = IssueAnomalyRule::factory()
        ->for($user)
        ->for($project)
        ->regression()
        ->create();
    $rule->alerts()->attach($alert->id);

    ingest($project);

    $issue = Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->firstOrFail();
    $issue->update(['status' => 'resolved', 'resolved_at' => now()->subHour()]);

    ingest($project);

    $issue->refresh();
    $event = Event::query()->withoutGlobalScope('user')->where('issue_id', $issue->id)->latest()->firstOrFail();

    (new EvaluateIssueAlertRulesJob($issue->id, $event->id, false, true))->handle();

    expect(IssueAnomaly::query()->where('rule_id', $rule->id)->count())->toBe(1);
    Bus::assertDispatchedTimes(SendIssueAlertNotificationJob::class, 1);
});

it('actually sends a notification through the existing alert channel', function () {
    Notification::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $alert = Alert::factory()->create(['user_id' => $user->id, 'type' => AlertType::EMAIL, 'destination' => 'who@b.test']);
    $rule = IssueAnomalyRule::factory()
        ->for($user)
        ->for($project)
        ->state(['condition_type' => IssueAlertCondition::FIRST_SEEN])
        ->create();
    $rule->alerts()->attach($alert->id);

    ingest($project);
    $issue = Issue::query()->withoutGlobalScope('user')->where('project_id', $project->id)->firstOrFail();
    $event = Event::query()->withoutGlobalScope('user')->where('issue_id', $issue->id)->latest()->firstOrFail();

    (new EvaluateIssueAlertRulesJob($issue->id, $event->id, true, false))->handle();

    Notification::assertSentTo($alert, IssueDetectedNotification::class);
});
