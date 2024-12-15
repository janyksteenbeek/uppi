<?php

namespace App\Models;

use App\Enums\Monitors\MonitorType;
use App\Enums\Checks\Status;
use App\Jobs\Checks\CheckJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Monitor extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'type' => MonitorType::class,
        'status' => Status::class,
        'last_checked_at' => 'datetime',
        'consecutive_threshold' => 'integer',
    ];

    protected static function booted()
    {
        if (Auth::hasUser()) {
            static::addGlobalScope('user', function (Builder $builder) {
                $builder->where('user_id', Auth::id());
            });

            static::creating(function ($monitor) {
                if (!$monitor->user_id) {
                    $monitor->user_id = Auth::id();
                }
            });
        }
    }

    public function alerts(): BelongsToMany
    {
        return $this->belongsToMany(Alert::class);
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    public function makeCheckJob(): CheckJob
    {
        return new ($this->type->toCheckJob())($this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDomainAttribute(): ?string
    {
        return parse_url($this->url, PHP_URL_HOST);
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

            if (!isset($allDays[$dateString])) {
                // No data for this day
                $status[$dateString] = null;
            } else {
                // If any record for this day had downtime, mark as false (down)
                $status[$dateString] = !$allDays[$dateString]->contains('had_downtime', true);
            }
        }

        return $status;
    }
}

