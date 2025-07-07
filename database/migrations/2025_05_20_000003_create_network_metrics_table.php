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
        Schema::create('network_metrics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('server_metric_id')->constrained()->cascadeOnDelete();
            $table->string('interface_name'); // e.g., 'eth0', 'wlan0', 'lo'
            $table->bigInteger('rx_bytes');
            $table->bigInteger('tx_bytes');
            $table->bigInteger('rx_packets')->nullable();
            $table->bigInteger('tx_packets')->nullable();
            $table->bigInteger('rx_errors')->nullable();
            $table->bigInteger('tx_errors')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['server_metric_id', 'interface_name']);
            $table->index(['server_metric_id', 'rx_bytes']);
            $table->index(['server_metric_id', 'tx_bytes']);
            $table->index('interface_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_metrics');
    }
};