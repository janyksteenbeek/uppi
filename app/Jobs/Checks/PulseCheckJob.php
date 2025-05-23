<?php

namespace App\Jobs\Checks;

use App\Enums\Checks\Status;
use App\Models\Monitor;

class PulseCheckJob extends CheckJob
{
    protected function performCheck(): array
    {
        // Get the last check-in time
        $lastCheckedAt = $this->monitor->last_checkin_at;

        // If no last check-in or the last check-in was more than the configured threshold time ago
        if ($lastCheckedAt === null || (now()->diffInMinutes($lastCheckedAt) * -1) > (int)$this->monitor->address) {
            return [
                'status' => Status::FAIL,
                'output' => 'Pulse check-in missed. Last check-in: '.
                    ($lastCheckedAt ? $lastCheckedAt->diffForHumans() : 'Never'),
            ];
        }

        return [
            'status' => Status::OK,
            'output' => 'Pulse check-in received within expected interval. Last check-in: '.
                $lastCheckedAt->diffForHumans(),
        ];
    }
}
