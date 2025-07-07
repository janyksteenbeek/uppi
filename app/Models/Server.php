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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[ObservedBy(UserIdObserver::class)]
class Server extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'os',
        'secret',
        'is_active',
        'last_seen_at',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    protected static function booted(): void
    {
        if (Auth::hasUser()) {
            static::addGlobalScope('user', function (Builder $builder) {
                $builder->where('user_id', Auth::id());
            });
        }

        static::creating(function (Server $server) {
            if (empty($server->secret)) {
                $server->secret = Str::random(64);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    public function recentMetrics()
    {
        return $this->metrics()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc');
    }

    public function latestMetric()
    {
        return $this->metrics()
            ->latest()
            ->first();
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function generateNewSecret(): string
    {
        $this->secret = Str::random(64);
        $this->save();
        
        return $this->secret;
    }
}