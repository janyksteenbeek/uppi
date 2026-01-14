<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkMetric extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'server_metric_id',
        'interface_name',
        'rx_bytes',
        'tx_bytes', 
        'rx_packets',
        'tx_packets',
        'rx_errors',
        'tx_errors',
    ];

    protected $casts = [
        'rx_bytes' => 'integer',
        'tx_bytes' => 'integer',
        'rx_packets' => 'integer',
        'tx_packets' => 'integer',
        'rx_errors' => 'integer',
        'tx_errors' => 'integer',
    ];

    public function serverMetric(): BelongsTo
    {
        return $this->belongsTo(ServerMetric::class);
    }

    public function getFormattedRxBytesAttribute(): string
    {
        return $this->formatBytes($this->rx_bytes);
    }

    public function getFormattedTxBytesAttribute(): string
    {
        return $this->formatBytes($this->tx_bytes);
    }

    public function getTotalBytesAttribute(): int
    {
        return $this->rx_bytes + $this->tx_bytes;
    }

    public function getFormattedTotalBytesAttribute(): string
    {
        return $this->formatBytes($this->total_bytes);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}