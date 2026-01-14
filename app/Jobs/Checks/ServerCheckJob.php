<?php

namespace App\Jobs\Checks;

use App\Enums\Checks\Status;
use App\Enums\Monitors\ServerMetricType;
use App\Models\Server;

class ServerCheckJob extends CheckJob
{
    protected function performCheck(): array
    {
        $server = Server::withoutGlobalScopes()->find($this->monitor->server_id);

        if (! $server) {
            return [
                'status' => Status::FAIL,
                'output' => json_encode([
                    'error' => 'Server not found',
                ]),
            ];
        }

        $latestMetric = $server->latestMetric();

        if (! $latestMetric) {
            return [
                'status' => Status::FAIL,
                'output' => json_encode([
                    'error' => 'No metrics available for this server',
                ]),
            ];
        }

        // Check if metrics are stale (older than 10 minutes)
        if ($latestMetric->created_at->diffInMinutes(now()) > 10) {
            return [
                'status' => Status::FAIL,
                'output' => json_encode([
                    'error' => 'Server metrics are stale',
                    'last_metric_at' => $latestMetric->created_at->toIso8601String(),
                ]),
            ];
        }

        $metricType = $this->monitor->metric_type;
        if (! $metricType) {
            return [
                'status' => Status::FAIL,
                'output' => json_encode([
                    'error' => 'Invalid metric type',
                ]),
            ];
        }

        $currentValue = $this->getCurrentMetricValue($latestMetric, $metricType);
        $threshold = $this->monitor->threshold;
        $operator = $this->monitor->threshold_operator;

        $exceeds = $this->checkThreshold($currentValue, $threshold, $operator);

        return [
            'status' => $exceeds ? Status::FAIL : Status::OK,
            'output' => json_encode([
                'metric_type' => $metricType->value,
                'current_value' => $currentValue,
                'threshold' => $threshold,
                'operator' => $operator,
                'server_name' => $server->name,
                'disk_mount_point' => $this->monitor->disk_mount_point,
            ]),
        ];
    }

    protected function getCurrentMetricValue($metric, ServerMetricType $type): float
    {
        return match ($type) {
            ServerMetricType::CpuUsage => $metric->cpu_usage ?? 0,
            ServerMetricType::MemoryUsage => $metric->memory_total > 0
                ? ($metric->memory_used / $metric->memory_total) * 100
                : 0,
            ServerMetricType::SwapUsage => $metric->swap_total > 0
                ? ($metric->swap_used / $metric->swap_total) * 100
                : 0,
            ServerMetricType::LoadAverage => $metric->cpu_load_1 ?? 0,
            ServerMetricType::DiskUsage => $this->getDiskUsage($metric),
        };
    }

    protected function getDiskUsage($metric): float
    {
        $mountPoint = $this->monitor->disk_mount_point;

        if (! $mountPoint) {
            // Get the highest disk usage if no specific mount point
            $disk = $metric->diskMetrics->sortByDesc(function ($d) {
                return $d->total_bytes > 0 ? ($d->used_bytes / $d->total_bytes) * 100 : 0;
            })->first();

            return $disk && $disk->total_bytes > 0
                ? ($disk->used_bytes / $disk->total_bytes) * 100
                : 0;
        }

        $disk = $metric->diskMetrics->firstWhere('mount_point', $mountPoint);

        if (! $disk) {
            return 0;
        }

        return $disk->total_bytes > 0
            ? ($disk->used_bytes / $disk->total_bytes) * 100
            : 0;
    }

    protected function checkThreshold(float $value, float $threshold, string $operator): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '=' => abs($value - $threshold) < 0.01,
            default => $value > $threshold,
        };
    }
}
