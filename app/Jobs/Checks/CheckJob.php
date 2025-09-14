<?php

namespace App\Jobs\Checks;

use App\Enums\Checks\Status;
use App\Models\Check;
use App\Models\Monitor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Sentry;

abstract class CheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected float $startTime;

    protected float $endTime;

    public function __construct(protected Monitor $monitor) {}

    public function handle(): void
    {
        $this->startTime = microtime(true);

        try {
            $result = $this->performCheck();
            $this->endTime = microtime(true);

            $this->processResult($result);
        } catch (ConnectionException|Exception $exception) {
            $this->handleException($exception);
        }

        $this->monitor->updateNextCheck();
    }

    abstract protected function performCheck(): array;

    protected function processResult(array $result): void
    {
        DB::transaction(function () use ($result) {
            $check = $this->createCheck($result);
            $this->updateMonitorStatus($check);
        });
    }

    protected function handleException(Exception $exception): void
    {
        $this->endTime = microtime(true);

        if (! $exception instanceof ConnectionException) {
            Log::error("Failed to perform monitor check {$this->monitor->id}: {$exception->getMessage()}");
            Sentry::captureException($exception);
        }

        $check = $this->createCheck([
            'status' => Status::FAIL,
            'output' => $exception->getMessage(),
        ]);

        $this->updateMonitorStatus($check);
    }

    protected function createCheck(array $result): Check
    {

        $check = new Check([
            'status' => $result['status'] ?? Status::FAIL,
            'response_time' => $this->calculateResponseTime(),
            'response_code' => $result['response_code'] ?? null,
            'output' => $result['output'] ?? null,
            'checked_at' => now(),
            'region' => config('services.checker.region'),
            'server_id' => config('services.checker.server_id'),
        ]);

        $this->monitor->checks()->save($check);

        return $check;
    }

    protected function updateMonitorStatus(Check $check): void
    {
        $this->monitor->refresh();
        $threshold = $this->monitor->consecutive_threshold;
        $regions = collect(config('services.checker.regions', []));

        if ($regions->isEmpty()) {
            $regions = $this->monitor->checks()
                ->select('region')
                ->whereNotNull('region')
                ->distinct()
                ->pluck('region');
        }

        // Always include current check's region
        if ($check->region && ! $regions->contains($check->region)) {
            $regions->push($check->region);
        }

        // Fallback: if we have no regions configured and no region data, use legacy global threshold logic
        if ($regions->isEmpty()) {
            $recentChecks = $this->monitor->checks()
                ->latest('checked_at')
                ->take($threshold)
                ->get();

            if ($recentChecks->count() >= $threshold) {
                $allSame = $recentChecks->every(fn ($c) => $c->status === $check->status);
                if ($allSame) {
                    $this->monitor->status = $check->status;
                    $this->monitor->save();
                }
            }

            return;
        }

        $anyRegionFailing = false;
        $allRegionsOk = true;

        foreach ($regions as $region) {
            $recentChecks = $this->monitor->checks()
                ->when($region, function ($query) use ($region) {
                    $query->where('region', $region);
                })
                ->latest('checked_at')
                ->take($threshold)
                ->get();

            if ($recentChecks->count() < $threshold) {
                $allRegionsOk = false; // lack of data, cannot claim healthy
                continue;
            }

            $allOk = $recentChecks->every(fn ($c) => $c->status === Status::OK);
            $allFail = $recentChecks->every(fn ($c) => $c->status === Status::FAIL);

            if ($allFail) {
                $anyRegionFailing = true;
            }

            if (! $allOk) {
                $allRegionsOk = false;
            }
        }

        if ($anyRegionFailing) {
            $this->monitor->status = Status::FAIL;
            $this->monitor->save();
        } elseif ($allRegionsOk && $regions->isNotEmpty()) {
            $this->monitor->status = Status::OK;
            $this->monitor->save();
        }
    }

    protected function calculateResponseTime(): float
    {
        return ($this->endTime - $this->startTime) * 1000; // Convert to milliseconds
    }
}
