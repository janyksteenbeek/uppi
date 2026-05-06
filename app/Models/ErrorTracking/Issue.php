<?php

namespace App\Models\ErrorTracking;

use App\Enums\ErrorTracking\IssueLevel;
use App\Enums\ErrorTracking\IssueStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Issue extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'error_issues';

    protected $guarded = [];

    protected $casts = [
        'level' => IssueLevel::class,
        'status' => IssueStatus::class,
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
        'ignored_until' => 'datetime',
        'times_seen' => 'integer',
        'users_seen' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::hasUser()) {
                $builder->whereHas('project', function ($query) {
                    $query->where('user_id', Auth::id());
                });
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function latestEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'latest_event_id');
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(IssueAnomaly::class);
    }
}
