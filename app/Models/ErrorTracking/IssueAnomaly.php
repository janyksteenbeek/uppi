<?php

namespace App\Models\ErrorTracking;

use App\Models\AlertTrigger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class IssueAnomaly extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'error_issue_anomalies';

    protected $guarded = [];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::hasUser()) {
                $builder->whereHas('issue.project', function ($query) {
                    $query->where('user_id', Auth::id());
                });
            }
        });
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(IssueAnomalyRule::class, 'rule_id');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(AlertTrigger::class, 'issue_anomaly_id');
    }
}
