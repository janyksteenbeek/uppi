<?php

namespace App\Models;

use App\Enums\Tests\TestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestRun extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected $casts = [
        'status' => TestStatus::class,
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function runSteps(): HasMany
    {
        return $this->hasMany(TestRunStep::class)->orderBy('sort_order');
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => TestStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markAsSuccess(int $durationMs): void
    {
        $this->update([
            'status' => TestStatus::SUCCESS,
            'duration_ms' => $durationMs,
            'finished_at' => now(),
        ]);
    }

    public function markAsFailure(int $durationMs): void
    {
        $this->update([
            'status' => TestStatus::FAILURE,
            'duration_ms' => $durationMs,
            'finished_at' => now(),
        ]);
    }

    public function getFailedStep(): ?TestRunStep
    {
        return $this->runSteps()->where('status', TestStatus::FAILURE)->first();
    }

    public function getCompletedStepsCount(): int
    {
        return $this->runSteps()->where('status', TestStatus::SUCCESS)->count();
    }
}
