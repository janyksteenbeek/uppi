<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_issue_anomaly_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('error_projects')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->string('condition_type');
            $table->unsignedInteger('threshold_count')->nullable();
            $table->unsignedInteger('threshold_window_minutes')->nullable();
            $table->unsignedInteger('throttle_window_minutes')->default(60);
            $table->json('level_filter')->nullable();
            $table->json('environment_filter')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_issue_anomaly_rules');
    }
};
