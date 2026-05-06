<?php

namespace App\Models;

use App\Enums\Alerts\AlertTriggerType;
use App\Models\ErrorTracking\Issue;
use App\Models\ErrorTracking\IssueAnomaly;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertTrigger extends Model
{
    use HasUlids;

    protected $fillable = [
        'anomaly_id',
        'alert_id',
        'monitor_id',
        'issue_anomaly_id',
        'issue_id',
        'type',
        'channels_notified',
        'metadata',
        'triggered_at',
    ];

    protected $casts = [
        'channels_notified' => 'array',
        'metadata' => 'array',
        'triggered_at' => 'datetime',
        'type' => AlertTriggerType::class,
    ];

    public function anomaly(): BelongsTo
    {
        return $this->belongsTo(Anomaly::class);
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function issueAnomaly(): BelongsTo
    {
        return $this->belongsTo(IssueAnomaly::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}
