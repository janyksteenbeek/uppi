<?php

namespace App\Models\ErrorTracking;

use App\Enums\ErrorTracking\IssueAlertCondition;
use App\Models\Alert;
use App\Models\User;
use App\Observers\UserIdObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[ObservedBy(UserIdObserver::class)]
class IssueAnomalyRule extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'error_issue_anomaly_rules';

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'condition_type' => IssueAlertCondition::class,
        'level_filter' => 'array',
        'environment_filter' => 'array',
        'threshold_count' => 'integer',
        'threshold_window_minutes' => 'integer',
        'throttle_window_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::hasUser()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function alerts(): BelongsToMany
    {
        return $this->belongsToMany(Alert::class, 'alert_issue_anomaly_rule', 'issue_anomaly_rule_id', 'alert_id');
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(IssueAnomaly::class, 'rule_id');
    }
}
