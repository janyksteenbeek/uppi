<?php

namespace App\Models;

use App\Enums\Checks\Status;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use App\Observers\CheckObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

#[ObservedBy(CheckObserver::class)]
class Check extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'status' => Status::class,
        'checked_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function anomaly(): BelongsTo
    {
        return $this->belongsTo(Anomaly::class);
    }
}
