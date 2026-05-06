<?php

namespace App\Jobs\ErrorTracking;

use App\Enums\Alerts\AlertTriggerType;
use App\Enums\ErrorTracking\IssueAlertCondition;
use App\Jobs\ErrorTracking\Notifications\SendIssueAlertNotificationJob;
use App\Models\Alert;
use App\Models\ErrorTracking\Event;
use App\Models\ErrorTracking\Issue;
use App\Models\ErrorTracking\IssueAnomaly;
use App\Models\ErrorTracking\IssueAnomalyRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateIssueAlertRulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $issueId,
        public string $eventId,
        public bool $isNewIssue,
        public bool $wasRegressed,
    ) {}

    public function handle(): void
    {
        /** @var Issue|null $issue */
        $issue = Issue::query()->withoutGlobalScope('user')->find($this->issueId);

        if (! $issue) {
            return;
        }

        /** @var Event|null $event */
        $event = Event::query()->withoutGlobalScope('user')->find($this->eventId);

        if (! $event) {
            return;
        }

        $rules = IssueAnomalyRule::query()->withoutGlobalScope('user')
            ->where('project_id', $issue->project_id)
            ->where('is_enabled', true)
            ->with('alerts')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->matchesEventFilters($rule, $event)) {
                continue;
            }

            $triggerType = $this->triggerTypeFor($rule);

            if (! $this->conditionMet($rule, $issue, $event)) {
                continue;
            }

            if ($this->isThrottled($rule, $issue)) {
                continue;
            }

            $anomaly = IssueAnomaly::query()->create([
                'issue_id' => $issue->id,
                'rule_id' => $rule->id,
                'triggered_at' => now(),
                'metadata' => [
                    'condition' => $rule->condition_type?->value,
                    'event_id' => $event->event_id,
                    'times_seen' => $issue->times_seen,
                ],
            ]);

            foreach ($rule->alerts as $alert) {
                if (! $alert instanceof Alert || ! $alert->is_enabled) {
                    continue;
                }

                SendIssueAlertNotificationJob::dispatch($anomaly, $alert, $triggerType)
                    ->onQueue(config('error-tracking.alerts_queue', 'alerts'));
            }
        }
    }

    private function matchesEventFilters(IssueAnomalyRule $rule, Event $event): bool
    {
        $levels = $rule->level_filter;
        if (is_array($levels) && $levels !== [] && ! in_array($event->level?->value, $levels, true)) {
            return false;
        }

        $environments = $rule->environment_filter;
        if (is_array($environments) && $environments !== [] && ! in_array($event->environment, $environments, true)) {
            return false;
        }

        return true;
    }

    private function triggerTypeFor(IssueAnomalyRule $rule): AlertTriggerType
    {
        return match ($rule->condition_type) {
            IssueAlertCondition::REGRESSION => AlertTriggerType::ISSUE_REGRESSED,
            default => AlertTriggerType::ISSUE_DETECTED,
        };
    }

    private function conditionMet(IssueAnomalyRule $rule, Issue $issue, Event $event): bool
    {
        return match ($rule->condition_type) {
            IssueAlertCondition::FIRST_SEEN => $this->isNewIssue,
            IssueAlertCondition::REGRESSION => $this->wasRegressed,
            IssueAlertCondition::EVENT_COUNT_THRESHOLD => $this->thresholdMet($rule, $issue),
            default => false,
        };
    }

    private function thresholdMet(IssueAnomalyRule $rule, Issue $issue): bool
    {
        $count = (int) ($rule->threshold_count ?? 0);
        $window = (int) ($rule->threshold_window_minutes ?? 0);

        if ($count <= 0 || $window <= 0) {
            return false;
        }

        $since = now()->subMinutes($window);

        $observed = Event::query()->withoutGlobalScope('user')
            ->where('issue_id', $issue->id)
            ->where('occurred_at', '>=', $since)
            ->count();

        return $observed >= $count;
    }

    private function isThrottled(IssueAnomalyRule $rule, Issue $issue): bool
    {
        $window = (int) ($rule->throttle_window_minutes ?? 0);

        if ($window <= 0) {
            return false;
        }

        return IssueAnomaly::query()
            ->where('issue_id', $issue->id)
            ->where('rule_id', $rule->id)
            ->where('triggered_at', '>=', now()->subMinutes($window))
            ->exists();
    }
}
