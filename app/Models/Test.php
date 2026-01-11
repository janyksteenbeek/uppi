<?php

namespace App\Models;

use App\Observers\UserIdObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

#[ObservedBy(UserIdObserver::class)]
class Test extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected $casts = [
        'last_run_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        if (Auth::hasUser()) {
            static::addGlobalScope('user', function (Builder $builder) {
                $builder->where('user_id', Auth::id());
            });
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class, 'address');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TestStep::class)->orderBy('sort_order');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }

    public function lastRun(): HasOne
    {
        return $this->hasOne(TestRun::class)->latestOfMany('started_at');
    }

    public function updateLastRun(): void
    {
        $this->update([
            'last_run_at' => now(),
        ]);
    }

    public function getDomainAttribute(): ?string
    {
        return parse_url($this->entrypoint_url, PHP_URL_HOST);
    }
}
