<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiskMetric extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'server_metric_id',
        'mount_point',
        'total_bytes',
        'used_bytes',
        'available_bytes',
        'usage_percent',
    ];

    protected $casts = [
        'total_bytes' => 'integer',
        'used_bytes' => 'integer', 
        'available_bytes' => 'integer',
        'usage_percent' => 'float',
    ];

    public function serverMetric(): BelongsTo
    {
        return $this->belongsTo(ServerMetric::class);
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->formatBytes($this->total_bytes);
    }

    public function getFormattedUsedAttribute(): string
    {
        return $this->formatBytes($this->used_bytes);
    }

    public function getFormattedAvailableAttribute(): string
    {
        return $this->formatBytes($this->available_bytes);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}