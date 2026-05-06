<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_issue_anomalies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('issue_id')->constrained('error_issues')->cascadeOnDelete();
            $table->foreignUlid('rule_id')->constrained('error_issue_anomaly_rules')->cascadeOnDelete();
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['issue_id', 'triggered_at']);
            $table->index(['rule_id', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_issue_anomalies');
    }
};
