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
        Schema::create('anomalies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('monitor_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('alert_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at')->index();
            $table->dateTime('ended_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('checks', function (Blueprint $table) {
            $table->foreignUlid('anomaly_id')->nullable()->constrained('anomalies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};
