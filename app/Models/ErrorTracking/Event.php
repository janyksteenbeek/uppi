<?php

namespace App\Models\ErrorTracking;

use App\Enums\ErrorTracking\IssueLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Event extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'error_events';

    protected $guarded = [];

    protected $casts = [
        'level' => IssueLevel::class,
        'exception' => 'array',
        'stacktrace' => 'array',
        'breadcrumbs' => 'array',
        'tags' => 'array',
        'contexts' => 'array',
        'request' => 'array',
        'user_context' => 'array',
        'extra' => 'array',
        'sdk' => 'array',
        'received_at' => 'datetime',
        'occurred_at' => 'datetime',
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

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
