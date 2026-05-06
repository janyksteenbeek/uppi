<?php

namespace App\Models;

use App\Enums\Checks\Status;
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
class StatusPage extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected static function booted()
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

    public function getStatusAttribute(): Status
    {
        return $this->isOk() ? Status::OK : Status::FAIL;
    }

    public function updates(): BelongsToMany
    {
        return $this->belongsToMany(Update::class);
    }

    public function isOk(): bool
    {
        // If there are no enabled items, consider the status page as OK
        if ($this->items()->where('is_enabled', true)->doesntExist()) {
            return true;
        }

        // Check if any enabled monitor is down
        return ! $this->items()
            ->where('is_enabled', true)
            ->whereHas('monitor', function ($query) {
                $query->where('status', Status::FAIL)
                    ->where('is_enabled', true);
            })
            ->exists();
    }

    public function items(): HasMany
    {
        return $this->hasMany(StatusPageItem::class);
    }
}
