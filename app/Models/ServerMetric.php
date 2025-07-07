<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerMetric extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'server_id',
        'cpu_usage',
        'cpu_load_1',
        'cpu_load_5', 
        'cpu_load_15',
        'memory_total',
        'memory_used',
        'memory_available',
        'memory_usage_percent',
        'swap_total',
        'swap_used',
        'swap_usage_percent',
        'collected_at',
    ];

    protected $casts = [
        'cpu_usage' => 'float',
        'cpu_load_1' => 'float',
        'cpu_load_5' => 'float',
        'cpu_load_15' => 'float',
        'memory_total' => 'integer',
        'memory_used' => 'integer',
        'memory_available' => 'integer',
        'memory_usage_percent' => 'float',
        'swap_total' => 'integer',
        'swap_used' => 'integer',
        'swap_usage_percent' => 'float',
        'collected_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function diskMetrics(): HasMany
    {
        return $this->hasMany(DiskMetric::class);
    }

    public function networkMetrics(): HasMany
    {
        return $this->hasMany(NetworkMetric::class);
    }

    public function getFormattedMemoryUsedAttribute(): string
    {
        return $this->formatBytes($this->memory_used);
    }

    public function getFormattedMemoryTotalAttribute(): string
    {
        return $this->formatBytes($this->memory_total);
    }

    public function getFormattedSwapUsedAttribute(): string
    {
        return $this->formatBytes($this->swap_used);
    }

    public function getFormattedSwapTotalAttribute(): string
    {
        return $this->formatBytes($this->swap_total);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }

    public function scopeForTimeRange($query, string $start, string $end)
    {
        return $query->whereBetween('collected_at', [$start, $end]);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('collected_at', '>=', now()->subHours($hours));
    }
}