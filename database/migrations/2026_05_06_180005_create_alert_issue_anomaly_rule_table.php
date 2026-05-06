<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_issue_anomaly_rule', function (Blueprint $table) {
            $table->foreignUlid('alert_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('issue_anomaly_rule_id')->constrained('error_issue_anomaly_rules')->cascadeOnDelete();
            $table->primary(['alert_id', 'issue_anomaly_rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_issue_anomaly_rule');
    }
};
