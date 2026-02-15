<?php

namespace App\Jobs;

use App\Enums\Checks\Status;
use App\Jobs\Notifications\SendAlertNotificationJob;
use App\Jobs\Notifications\SendRecoveryNotificationJob;
use App\Models\Alert;
use App\Models\Anomaly;
use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TriggerAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 60;

    public function __construct(
        protected Check $check
    ) {}

    public function uniqueId(): string
    {
        return 'trigger_alert_'.$this->check->id;
    }

    public function handle(): void
    {
        $monitor = $this->check->monitor;
        $this->check->refresh();

        if ($this->check->status === Status::FAIL) {
            $this->handleMonitorDown($monitor);
        } else {
            $this->handleMonitorRecovery($monitor);
        }
    }

    protected function handleMonitorDown(Monitor $monitor): void
    {
        DB::transaction(function () use ($monitor) {
            $activeAnomaly = $this->getActiveAnomaly($monitor);

            // If there's already an active anomaly, just associate the check
            if ($activeAnomaly) {
                $this->associateCheckWithAnomaly($activeAnomaly);

                return;
            }

            // Get recent checks in chronological order for proper threshold checking
            $recentChecks = $this->getRecentChecks($monitor);

            // Only proceed if we have enough consecutive failures
            if ($this->hasMetFailureThreshold($recentChecks)) {
                // Update monitor status to FAIL now that threshold is met
                $monitor->update(['status' => Status::FAIL]);

                // Find the first failed check in the sequence (this is when the problem started)
                $firstFailedCheck = $recentChecks->reverse()->first();

                // Create anomaly starting from the first failure
                $anomaly = $this->createAnomaly($monitor, $firstFailedCheck);

                // Associate all failed checks with the anomaly
                $this->associateChecksWithAnomaly($monitor, $firstFailedCheck, $anomaly);

                // Send notifications
                $this->notifyAlerts($monitor, $anomaly, SendAlertNotificationJob::class);
            }
        });
    }

    protected function handleMonitorRecovery(Monitor $monitor): void
    {
        DB::transaction(function () use ($monitor) {
            $activeAnomaly = $this->getActiveAnomaly($monitor);
            if (! $activeAnomaly) {
                return;
            }

            // Get recent checks in chronological order
            $recentChecks = $this->getRecentChecks($monitor);

            // Only proceed if we have enough consecutive successes
            if ($this->hasMetRecoveryThreshold($recentChecks)) {
                // Update monitor status to OK now that recovery threshold is met
                $monitor->update(['status' => Status::OK]);

                // Find the first successful check in the sequence
                $firstSuccessCheck = $recentChecks->reverse()->first();

                // Close the anomaly
                $this->closeAnomaly($activeAnomaly, $firstSuccessCheck);

                // Associate all successful checks with the anomaly
                $this->associateChecksWithAnomaly($monitor, $firstSuccessCheck, $activeAnomaly);

                // Send recovery notifications
                $this->notifyAlerts($monitor, $activeAnomaly, SendRecoveryNotificationJob::class);
            } else {
                // If we don't have enough successes yet, just associate the check
                $this->associateCheckWithAnomaly($activeAnomaly);
            }
        });
    }

    protected function getActiveAnomaly(Monitor $monitor): ?Anomaly
    {
        $region = $this->check->region;
        return $monitor->anomalies()
            ->whereNull('ended_at')
            ->when($region, fn ($q) => $q->where('region', $region))
            ->lockForUpdate()
            ->first();
    }

    protected function getRecentChecks(Monitor $monitor): Collection
    {
        $region = $this->check->region;
        return $monitor->checks()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->latest('checked_at')
            ->take($monitor->consecutive_threshold)
            ->get()
            ->sortBy('checked_at'); // Sort chronologically after fetching
    }

    protected function hasMetFailureThreshold(Collection $checks): bool
    {
        return $this->hasMetThreshold($checks, Status::FAIL);
    }

    protected function hasMetRecoveryThreshold(Collection $checks): bool
    {
        return $this->hasMetThreshold($checks, Status::OK);
    }

    protected function hasMetThreshold(Collection $checks, Status $status): bool
    {
        $threshold = $this->check->monitor->consecutive_threshold;

        return $checks->count() >= $threshold &&
            $checks->every(fn ($check) => $check->status === $status);
    }

    protected function createAnomaly(Monitor $monitor, Check $firstFailedCheck): Anomaly
    {
        $anomaly = new Anomaly([
            'started_at' => $firstFailedCheck->checked_at,
            'monitor_id' => $monitor->id,
            'region' => $firstFailedCheck->region,
        ]);

        $anomaly->save();

        return $anomaly;
    }

    protected function closeAnomaly(Anomaly $anomaly, Check $firstSuccessCheck): void
    {
        $anomaly->ended_at = $firstSuccessCheck->checked_at;
        $anomaly->save();
    }

    protected function associateChecksWithAnomaly(Monitor $monitor, Check $startingCheck, Anomaly $anomaly): void
    {
        $status = $startingCheck->status;
        $region = $startingCheck->region;

        $query = $monitor->checks()
            ->where('checked_at', '>=', $startingCheck->checked_at)
            ->where('status', $status);

        if ($region) {
            $query->where('region', $region);
        }

        $query->update(['anomaly_id' => $anomaly->id]);
    }

    protected function associateCheckWithAnomaly(Anomaly $anomaly): void
    {
        $this->check->anomaly()->associate($anomaly);
        $this->check->save();
    }

    protected function notifyAlerts(Monitor $monitor, Anomaly $anomaly, string $jobClass): void
    {
        $monitor->alerts
            ->filter->is_enabled
            ->each(fn (Alert $alert) => dispatch(new $jobClass($anomaly, $alert)));
    }
}
