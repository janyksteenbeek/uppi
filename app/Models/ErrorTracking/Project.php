<?php

namespace App\Models\ErrorTracking;

use App\Models\User;
use App\Observers\UserIdObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[ObservedBy(UserIdObserver::class)]
class Project extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'error_projects';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_event_at' => 'datetime',
        'internal_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::hasUser()) {
                $builder->where('user_id', Auth::id());
            }
        });

        static::creating(function (Project $project) {
            if (empty($project->public_key)) {
                $project->public_key = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(IssueAnomalyRule::class);
    }

    public function getDsnAttribute(): string
    {
        $url = parse_url(config('app.url'));
        $scheme = $url['scheme'] ?? 'https';
        $host = $url['host'] ?? 'localhost';
        $port = isset($url['port']) ? ':'.$url['port'] : '';

        return sprintf('%s://%s@%s%s/%d', $scheme, $this->public_key, $host, $port, $this->internal_id);
    }
}
