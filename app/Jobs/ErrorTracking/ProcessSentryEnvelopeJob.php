<?php

namespace App\Jobs\ErrorTracking;

use App\Enums\ErrorTracking\IssueLevel;
use App\Enums\ErrorTracking\IssueStatus;
use App\Models\ErrorTracking\Event;
use App\Models\ErrorTracking\Issue;
use App\Models\ErrorTracking\Project;
use App\Services\ErrorTracking\Envelope\EnvelopeParser;
use App\Services\ErrorTracking\Envelope\InvalidEnvelopeException;
use App\Services\ErrorTracking\Envelope\SentryEventPayload;
use App\Services\ErrorTracking\FingerprintGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSentryEnvelopeJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 60;

    public function __construct(
        public string $projectId,
        public string $body,
    ) {}

    public function uniqueId(): string
    {
        return 'sentry_envelope_'.sha1($this->projectId.'|'.$this->body);
    }

    public function handle(EnvelopeParser $parser, FingerprintGenerator $fingerprints): void
    {
        $project = Project::query()->withoutGlobalScope('user')->find($this->projectId);

        if (! $project) {
            return;
        }

        try {
            $envelope = $parser->parse($this->body);
        } catch (InvalidEnvelopeException $e) {
            Log::warning('Invalid Sentry envelope received', [
                'project_id' => $this->projectId,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        foreach ($envelope['items'] as $item) {
            $type = (string) ($item['header']['type'] ?? '');
            if ($type !== 'event' && $type !== 'error') {
                continue;
            }

            $payload = $item['payload'];
            if (! is_array($payload)) {
                continue;
            }

            try {
                $this->processEvent($project, new SentryEventPayload($payload), $fingerprints);
            } catch (Throwable $e) {
                Log::error('Failed to process Sentry event', [
                    'project_id' => $project->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processEvent(Project $project, SentryEventPayload $event, FingerprintGenerator $fingerprints): void
    {
        $hash = $fingerprints->hash($event);
        $occurredAt = $event->occurredAt();
        $level = $event->level();

        $title = $this->buildTitle($event);
        $culprit = $this->buildCulprit($event);

        $stacktrace = $this->normalizeStacktrace($event);

        $userContext = $event->user();

        DB::transaction(function () use ($project, $event, $hash, $occurredAt, $level, $title, $culprit, $stacktrace, $userContext) {
            /** @var Issue $issue */
            $issue = Issue::query()->withoutGlobalScope('user')
                ->where('project_id', $project->id)
                ->where('fingerprint_hash', $hash)
                ->lockForUpdate()
                ->first();

            $isNew = false;
            $wasRegressed = false;

            if (! $issue) {
                $issue = new Issue([
                    'project_id' => $project->id,
                    'fingerprint_hash' => $hash,
                    'title' => $title,
                    'culprit' => $culprit,
                    'platform' => $event->platform(),
                    'level' => $level,
                    'status' => IssueStatus::OPEN->value,
                    'times_seen' => 0,
                    'users_seen' => 0,
                    'first_seen_at' => $occurredAt,
                    'last_seen_at' => $occurredAt,
                ]);
                $issue->save();
                $isNew = true;
            }

            if ($issue->status === IssueStatus::RESOLVED) {
                $issue->status = IssueStatus::OPEN;
                $issue->resolved_at = null;
                $wasRegressed = true;
            }

            $issue->times_seen = $issue->times_seen + 1;

            if ($occurredAt->greaterThan($issue->last_seen_at ?? $occurredAt)) {
                $issue->last_seen_at = $occurredAt;
            }

            if ($issue->first_seen_at === null || $occurredAt->lessThan($issue->first_seen_at)) {
                $issue->first_seen_at = $occurredAt;
            }

            $incomingLevel = IssueLevel::resolve($level);
            $currentLevel = $issue->level instanceof IssueLevel ? $issue->level : IssueLevel::resolve($issue->level);
            if ($incomingLevel && $currentLevel && $incomingLevel->isAtLeast($currentLevel)) {
                $issue->level = $incomingLevel;
            }

            if ($userContext !== null && ! empty($userContext['id'])) {
                $issue->users_seen = $issue->users_seen + 1;
            }

            $eventModel = new Event([
                'project_id' => $project->id,
                'issue_id' => $issue->id,
                'event_id' => $event->eventId() ?: bin2hex(random_bytes(16)),
                'level' => $level,
                'platform' => $event->platform(),
                'environment' => $event->environment(),
                'release' => $event->release(),
                'server_name' => $event->serverName(),
                'transaction' => $event->transaction(),
                'message' => $event->message(),
                'culprit' => $culprit,
                'exception' => $event->exception(),
                'stacktrace' => $stacktrace,
                'breadcrumbs' => $event->breadcrumbs(),
                'tags' => $event->tags(),
                'contexts' => $event->contexts(),
                'request' => $event->request(),
                'user_context' => $userContext,
                'extra' => $event->extra(),
                'sdk' => $event->sdk(),
                'received_at' => now(),
                'occurred_at' => $occurredAt,
            ]);
            $eventModel->save();

            $issue->latest_event_id = $eventModel->id;
            $issue->save();

            $project->forceFill(['last_event_at' => now()])->save();

            EvaluateIssueAlertRulesJob::dispatch(
                $issue->id,
                $eventModel->id,
                $isNew,
                $wasRegressed,
            )->onQueue(config('error-tracking.alerts_queue', 'alerts'));
        });
    }

    private function buildTitle(SentryEventPayload $event): string
    {
        $type = $event->exceptionType();
        $value = $event->exceptionValue();

        if ($type !== null && $type !== '') {
            return $value !== null && $value !== '' ? "{$type}: {$value}" : $type;
        }

        $message = $event->message();
        if ($message !== null && $message !== '') {
            return $message;
        }

        return 'Unknown error';
    }

    private function buildCulprit(SentryEventPayload $event): ?string
    {
        $frames = $event->frames();

        if ($frames === []) {
            return $event->transaction();
        }

        $reversed = array_reverse($frames);

        foreach ($reversed as $frame) {
            if (($frame['in_app'] ?? null) === true) {
                return $this->frameLabel($frame);
            }
        }

        return $this->frameLabel($reversed[0]);
    }

    /**
     * @param  array<string, mixed>  $frame
     */
    private function frameLabel(array $frame): string
    {
        $function = (string) ($frame['function'] ?? '');
        $filename = (string) ($frame['filename'] ?? $frame['module'] ?? '');

        if ($function !== '' && $filename !== '') {
            return $function.' ('.$filename.')';
        }

        return $function !== '' ? $function : $filename;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeStacktrace(SentryEventPayload $event): ?array
    {
        $frames = $event->frames();

        if ($frames === []) {
            return null;
        }

        foreach ($frames as $i => $frame) {
            if (! array_key_exists('in_app', $frame)) {
                $frames[$i]['in_app'] = $this->guessInApp($frame);
            }
        }

        return ['frames' => $frames];
    }

    /**
     * @param  array<string, mixed>  $frame
     */
    private function guessInApp(array $frame): bool
    {
        $filename = (string) ($frame['filename'] ?? $frame['abs_path'] ?? '');

        if ($filename === '') {
            return false;
        }

        return ! str_contains($filename, '/vendor/') && ! str_contains($filename, '\\vendor\\');
    }
}
