<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\DiskMetric;
use App\Models\NetworkMetric;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ServerMonitoringController extends Controller
{
    /**
     * Receive server metrics from monitoring daemon
     */
    public function report(Request $request, string $serverId): JsonResponse
    {
        try {
            // Find the server
            $server = Server::findOrFail($serverId);

            // Verify HMAC signature
            if (!$this->verifyHmacSignature($request, $server->secret)) {
                Log::warning('Invalid HMAC signature for server monitoring', [
                    'server_id' => $serverId,
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'error' => 'Invalid signature'
                ], 401);
            }

            // Validate the incoming data
            $validated = $request->validate([
                'cpu_usage' => 'nullable|numeric|min:0|max:100',
                'cpu_load_1' => 'nullable|numeric|min:0',
                'cpu_load_5' => 'nullable|numeric|min:0',
                'cpu_load_15' => 'nullable|numeric|min:0',
                'memory_total' => 'nullable|integer|min:0',
                'memory_used' => 'nullable|integer|min:0',
                'memory_available' => 'nullable|integer|min:0',
                'memory_usage_percent' => 'nullable|numeric|min:0|max:100',
                'swap_total' => 'nullable|integer|min:0',
                'swap_used' => 'nullable|integer|min:0',
                'swap_usage_percent' => 'nullable|numeric|min:0|max:100',
                'disk_metrics' => 'nullable|array',
                'disk_metrics.*.mount_point' => 'required_with:disk_metrics|string',
                'disk_metrics.*.total_bytes' => 'required_with:disk_metrics|integer|min:0',
                'disk_metrics.*.used_bytes' => 'required_with:disk_metrics|integer|min:0',
                'disk_metrics.*.available_bytes' => 'required_with:disk_metrics|integer|min:0',
                'disk_metrics.*.usage_percent' => 'nullable|numeric|min:0|max:100',
                'network_metrics' => 'nullable|array',
                'network_metrics.*.interface_name' => 'required_with:network_metrics|string',
                'network_metrics.*.rx_bytes' => 'required_with:network_metrics|integer|min:0',
                'network_metrics.*.tx_bytes' => 'required_with:network_metrics|integer|min:0',
                'network_metrics.*.rx_packets' => 'nullable|integer|min:0',
                'network_metrics.*.tx_packets' => 'nullable|integer|min:0',
                'network_metrics.*.rx_errors' => 'nullable|integer|min:0',
                'network_metrics.*.tx_errors' => 'nullable|integer|min:0',
                'collected_at' => 'nullable|date',
            ]);

            // Create the server metric record
            $metric = ServerMetric::create([
                'server_id' => $server->id,
                'cpu_usage' => $validated['cpu_usage'] ?? null,
                'cpu_load_1' => $validated['cpu_load_1'] ?? null,
                'cpu_load_5' => $validated['cpu_load_5'] ?? null,
                'cpu_load_15' => $validated['cpu_load_15'] ?? null,
                'memory_total' => $validated['memory_total'] ?? null,
                'memory_used' => $validated['memory_used'] ?? null,
                'memory_available' => $validated['memory_available'] ?? null,
                'memory_usage_percent' => $validated['memory_usage_percent'] ?? null,
                'swap_total' => $validated['swap_total'] ?? null,
                'swap_used' => $validated['swap_used'] ?? null,
                'swap_usage_percent' => $validated['swap_usage_percent'] ?? null,
                'collected_at' => $validated['collected_at'] ? 
                    \Carbon\Carbon::parse($validated['collected_at']) : now(),
            ]);

            // Create disk metric records
            if (isset($validated['disk_metrics']) && is_array($validated['disk_metrics'])) {
                foreach ($validated['disk_metrics'] as $diskData) {
                    DiskMetric::create([
                        'server_metric_id' => $metric->id,
                        'mount_point' => $diskData['mount_point'],
                        'total_bytes' => $diskData['total_bytes'],
                        'used_bytes' => $diskData['used_bytes'],
                        'available_bytes' => $diskData['available_bytes'],
                        'usage_percent' => $diskData['usage_percent'] ?? 
                            (($diskData['used_bytes'] / max($diskData['total_bytes'], 1)) * 100),
                    ]);
                }
            }

            // Create network metric records
            if (isset($validated['network_metrics']) && is_array($validated['network_metrics'])) {
                foreach ($validated['network_metrics'] as $networkData) {
                    NetworkMetric::create([
                        'server_metric_id' => $metric->id,
                        'interface_name' => $networkData['interface_name'],
                        'rx_bytes' => $networkData['rx_bytes'],
                        'tx_bytes' => $networkData['tx_bytes'],
                        'rx_packets' => $networkData['rx_packets'] ?? null,
                        'tx_packets' => $networkData['tx_packets'] ?? null,
                        'rx_errors' => $networkData['rx_errors'] ?? null,
                        'tx_errors' => $networkData['tx_errors'] ?? null,
                    ]);
                }
            }

            // Update server's last_seen_at
            $server->update(['last_seen_at' => now()]);

            Log::info('Server metrics received successfully', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'metric_id' => $metric->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Metrics received successfully',
                'metric_id' => $metric->id,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (ValidationException $e) {
            Log::warning('Invalid server monitoring data received', [
                'server_id' => $serverId,
                'errors' => $e->errors(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to process server monitoring data', [
                'server_id' => $serverId,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get server configuration (useful for daemon setup)
     */
    public function getConfig(Request $request, string $serverId): JsonResponse
    {
        try {
            $server = Server::findOrFail($serverId);

            // Verify HMAC signature
            if (!$this->verifyHmacSignature($request, $server->secret)) {
                return response()->json([
                    'error' => 'Invalid signature'
                ], 401);
            }

            return response()->json([
                'server_id' => $server->id,
                'name' => $server->name,
                'hostname' => $server->hostname,
                'is_active' => $server->is_active,
                'report_url' => route('api.server.report', $server->id),
                'last_seen_at' => $server->last_seen_at?->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get server config', [
                'server_id' => $serverId,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Verify HMAC signature for request authentication
     */
    private function verifyHmacSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        
        if (!$signature || !$timestamp) {
            return false;
        }

        // Check if timestamp is within acceptable range (5 minutes)
        $requestTime = (int) $timestamp;
        $currentTime = time();
        
        if (abs($currentTime - $requestTime) > 300) {
            Log::warning('Request timestamp too old or in future', [
                'request_time' => $requestTime,
                'current_time' => $currentTime,
                'difference' => abs($currentTime - $requestTime),
            ]);
            return false;
        }

        // Create expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $timestamp . $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Cleanup old metrics (can be called via cron)
     */
    public function cleanup(): JsonResponse
    {
        try {
            // Delete metrics older than 30 days by default
            $cutoffDate = now()->subDays(30);
            $deleted = ServerMetric::where('created_at', '<', $cutoffDate)->delete();

            Log::info('Server metrics cleanup completed', [
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoffDate->toISOString(),
            ]);

            return response()->json([
                'success' => true,
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoffDate->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cleanup server metrics', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Cleanup failed'
            ], 500);
        }
    }
}