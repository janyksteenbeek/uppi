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
        Schema::create('disk_metrics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('server_metric_id')->constrained()->cascadeOnDelete();
            $table->string('mount_point'); // e.g., '/', '/var', '/home'
            $table->bigInteger('total_bytes');
            $table->bigInteger('used_bytes');
            $table->bigInteger('available_bytes');
            $table->float('usage_percent', 5, 2);
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['server_metric_id', 'mount_point']);
            $table->index(['server_metric_id', 'usage_percent']);
            $table->index('mount_point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disk_metrics');
    }
};