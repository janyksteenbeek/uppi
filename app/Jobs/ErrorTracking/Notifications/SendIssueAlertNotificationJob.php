<?php

namespace App\Jobs\ErrorTracking\Notifications;

use App\Enums\Alerts\AlertTriggerType;
use App\Models\Alert;
use App\Models\AlertTrigger;
use App\Models\ErrorTracking\IssueAnomaly;
use App\Notifications\ErrorTracking\IssueDetectedNotification;
use App\Notifications\ErrorTracking\IssueRegressedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendIssueAlertNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected IssueAnomaly $anomaly,
        protected Alert $alert,
        protected AlertTriggerType $triggerType,
    ) {}

    public function handle(): void
    {
        if (! $this->alert->is_enabled) {
            return;
        }

        $issue = $this->anomaly->issue;

        AlertTrigger::query()->create([
            'alert_id' => $this->alert->id,
            'issue_anomaly_id' => $this->anomaly->id,
            'issue_id' => $issue?->id,
            'type' => $this->triggerType,
            'channels_notified' => [$this->alert->type?->value],
            'metadata' => [
                'project_id' => $issue?->project_id,
                'issue_title' => $issue?->title,
                'issue_culprit' => $issue?->culprit,
                'rule_id' => $this->anomaly->rule_id,
                'times_seen' => $issue?->times_seen,
            ],
            'triggered_at' => now(),
        ]);

        $notification = match ($this->triggerType) {
            AlertTriggerType::ISSUE_REGRESSED => new IssueRegressedNotification($this->anomaly),
            default => new IssueDetectedNotification($this->anomaly),
        };

        $this->alert->notify($notification);
    }
}
