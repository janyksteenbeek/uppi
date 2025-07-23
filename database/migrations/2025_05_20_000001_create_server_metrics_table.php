<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('server_id')->constrained()->cascadeOnDelete();
            
            // CPU Metrics
            $table->float('cpu_usage', 5, 2)->nullable(); // CPU usage percentage
            $table->float('cpu_load_1', 8, 2)->nullable(); // 1-minute load average
            $table->float('cpu_load_5', 8, 2)->nullable(); // 5-minute load average
            $table->float('cpu_load_15', 8, 2)->nullable(); // 15-minute load average
            
            // Memory Metrics (in bytes)
            $table->bigInteger('memory_total')->nullable();
            $table->bigInteger('memory_used')->nullable();
            $table->bigInteger('memory_available')->nullable();
            $table->float('memory_usage_percent', 5, 2)->nullable();
            
            // Swap Metrics (in bytes)
            $table->bigInteger('swap_total')->nullable();
            $table->bigInteger('swap_used')->nullable();
            $table->float('swap_usage_percent', 5, 2)->nullable();
            
            // Disk and Network metrics are stored in separate tables for better queryability
            
            $table->timestamp('collected_at'); // When the metrics were collected
            $table->timestamps();

            // Critical indexes for performance with large datasets
            $table->index(['server_id', 'collected_at']);
            $table->index(['server_id', 'created_at']);
            $table->index('collected_at');
            
            // Compound indexes for common queries
            $table->index(['server_id', 'collected_at', 'cpu_usage']);
            $table->index(['server_id', 'collected_at', 'memory_usage_percent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};