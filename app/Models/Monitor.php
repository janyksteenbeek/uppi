<?php

namespace App\Models;

use App\Enums\Checks\Status;
use App\Enums\Monitors\MonitorType;
use App\Enums\Monitors\ServerMetricType;
use App\Jobs\Checks\CheckJob;
use App\Observers\UserIdObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

#[ObservedBy(UserIdObserver::class)]
class Monitor extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'type' => MonitorType::class,
        'status' => Status::class,
        'last_checked_at' => 'datetime',
        'last_checkin_at' => 'datetime',
        'next_check_at' => 'datetime',
        'consecutive_threshold' => 'integer',
        'auto_create_update' => 'boolean',
        'update_values' => 'array',
        'metric_type' => ServerMetricType::class,
        'threshold' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        if (Auth::hasUser()) {
            static::addGlobalScope('user', function (Builder $builder) {
                $builder->where('user_id', Auth::id());
            });
        }

        static::creating(function (Monitor $monitor) {
            $monitor->next_check_at = now();
        });
    }

    public function alerts(): BelongsToMany
    {
        return $this->belongsToMany(Alert::class);
    }

    public function makeCheckJob(): CheckJob
    {
        return new ($this->type->toCheckJob())($this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the server for this monitor (when type is SERVER).
     * Uses the address field to store the server ID.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'address');
    }

    public function updates(): BelongsToMany
    {
        return $this->belongsToMany(Update::class);
    }

    public function getDomainAttribute(): ?string
    {
        return parse_url($this->address, PHP_URL_HOST);
    }

    public function status30Days(): array
    {
        $today = Carbon::today();
        $thirtyDaysAgo = $today->copy()->subDays(29);

        // Get all anomalies in the last 30 days
        $anomalies = $this->anomalies()
            ->where('started_at', '>=', $thirtyDaysAgo)
            ->get()
            ->map(function ($anomaly) {
                return [
                    'date' => Carbon::parse($anomaly->started_at)->startOfDay(),
                    'had_downtime' => true,
                ];
            });

        // Get all days where we had checks (for uptime)
        $checks = $this->checks()
            ->where('checked_at', '>=', $thirtyDaysAgo)
            ->get()
            ->groupBy(function ($check) {
                return Carbon::parse($check->checked_at)->startOfDay()->toDateString();
            })
            ->map(function ($dayChecks) {
                return [
                    'date' => Carbon::parse($dayChecks->first()->checked_at)->startOfDay(),
                    'had_downtime' => false,
                ];
            });

        // Merge anomalies and checks
        $allDays = $anomalies->concat($checks)
            ->groupBy(function ($item) {
                return $item['date']->toDateString();
            });

        // Build the 30-day array with dates as keys
        $status = [];
        for ($date = $thirtyDaysAgo; $date <= $today; $date = $date->copy()->addDay()) {
            $dateString = $date->toDateString();

            if (! isset($allDays[$dateString])) {
                // No data for this day
                $status[$dateString] = null;
            } else {
                // If any record for this day had downtime, mark as false (down)
                $status[$dateString] = ! $allDays[$dateString]->contains('had_downtime', true);
            }
        }

        return $status;
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    public function lastCheck(): HasOne
    {
        return $this->hasOne(Check::class)->latestOfMany('checked_at');
    }

    /**
     * Get the latest status per configured region for this monitor.
     * Regions with no data will be included with null status.
     */
    public function regionStatuses(): array
    {
        $regions = collect(config('services.checker.regions', []));

        // If no regions configured, derive from existing data
        if ($regions->isEmpty()) {
            $regions = $this->checks()
                ->select('region')
                ->whereNotNull('region')
                ->distinct()
                ->pluck('region');
        }

        $statuses = [];
        foreach ($regions as $region) {
            $lastCheck = $this->checks()
                ->where('region', $region)
                ->latest('checked_at')
                ->first();
            $statuses[$region] = $lastCheck?->status;
        }

        return $statuses;
    }

    public function updateNextCheck(): void
    {
        $this->update([
            'last_checked_at' => now(),
            'next_check_at' => now()->addMinutes($this->interval),
        ]);
    }

    /**
     * Get the status page items for this monitor
     */
    public function statusPageItems(): HasMany
    {
        return $this->hasMany(StatusPageItem::class);
    }

    /**
     * Get the test for this monitor (when type is TEST)
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'address');
    }
}
