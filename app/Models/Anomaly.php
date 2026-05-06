<?php

namespace App\Models;

use App\Observers\AnomalyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[ObservedBy(AnomalyObserver::class)]
class Anomaly extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_create_update' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::hasUser()) {
                $builder->whereHas('monitor', function ($query) {
                    $query->where('user_id', Auth::id());
                });
            }
        });
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
