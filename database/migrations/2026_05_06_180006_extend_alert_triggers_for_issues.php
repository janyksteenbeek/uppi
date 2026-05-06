<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildForSqlite();

            return;
        }

        Schema::table('alert_triggers', function (Blueprint $table) {
            $table->foreignUlid('issue_anomaly_id')->nullable()->after('anomaly_id')
                ->constrained('error_issue_anomalies')->cascadeOnDelete();
            $table->foreignUlid('issue_id')->nullable()->after('issue_anomaly_id')
                ->constrained('error_issues')->cascadeOnDelete();
            $table->foreignUlid('anomaly_id')->nullable()->change();
            $table->foreignUlid('monitor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('alert_triggers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issue_anomaly_id');
            $table->dropConstrainedForeignId('issue_id');
        });
    }

    private function rebuildForSqlite(): void
    {
        DB::statement('DROP INDEX IF EXISTS alert_triggers_type_index');

        Schema::rename('alert_triggers', 'alert_triggers_legacy_for_issues');

        Schema::create('alert_triggers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('anomaly_id')->nullable();
            $table->foreignUlid('alert_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('monitor_id')->nullable();
            $table->foreignUlid('issue_anomaly_id')->nullable();
            $table->foreignUlid('issue_id')->nullable();
            $table->string('type')->index();
            $table->json('channels_notified');
            $table->json('metadata')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO alert_triggers (id, anomaly_id, alert_id, monitor_id, type, channels_notified, metadata, triggered_at, created_at, updated_at)
            SELECT id, anomaly_id, alert_id, monitor_id, type, channels_notified, metadata, triggered_at, created_at, updated_at
            FROM alert_triggers_legacy_for_issues
        ');

        Schema::drop('alert_triggers_legacy_for_issues');
    }
};
