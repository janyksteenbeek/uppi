<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Anomaly extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected static function booted()
    {
        if (Auth::hasUser()) {
            static::addGlobalScope('user', function (Builder $builder) {
                $builder->whereHas('monitor', function ($query) {
                    $query->where('user_id', Auth::id());
                });
            });
        }
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(AlertTrigger::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }
}
