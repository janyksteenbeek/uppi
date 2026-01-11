<?php

namespace App\Models;

use App\Enums\Tests\TestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRunStep extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected $casts = [
        'status' => TestStatus::class,
        'sort_order' => 'integer',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }

    public function testStep(): BelongsTo
    {
        return $this->belongsTo(TestStep::class);
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

    public function markAsFailure(string $errorMessage, int $durationMs, ?string $screenshotPath = null, ?string $htmlSnapshot = null): void
    {
        $this->update([
            'status' => TestStatus::FAILURE,
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
            'screenshot_path' => $screenshotPath,
            'html_snapshot' => $htmlSnapshot,
            'finished_at' => now(),
        ]);
    }
}
