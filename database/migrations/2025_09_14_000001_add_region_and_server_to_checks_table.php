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
        Schema::table('checks', function (Blueprint $table) {
            $table->string('region', 64)->nullable()->index()->after('status');
            $table->string('server_id', 128)->nullable()->after('region');

            // Helpful composite indexes for common queries
            $table->index(['monitor_id', 'region', 'checked_at']);
            $table->index(['monitor_id', 'region', 'status', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checks', function (Blueprint $table) {
            $table->dropIndex('checks_monitor_id_region_checked_at_index');
            $table->dropIndex('checks_monitor_id_region_status_checked_at_index');
            $table->dropColumn(['region', 'server_id']);
        });
    }
};

