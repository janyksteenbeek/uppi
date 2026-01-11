<?php

namespace App\Models;

use App\Enums\Tests\TestFlowBlockType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestStep extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected $casts = [
        'type' => TestFlowBlockType::class,
        'sort_order' => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function runSteps(): HasMany
    {
        return $this->hasMany(TestRunStep::class);
    }
}
