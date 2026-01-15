<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            // Server ID is stored in the `address` column (same pattern as TEST monitors)
            $table->string('metric_type')->nullable()->after('address');
            $table->decimal('threshold', 10, 2)->nullable()->after('metric_type');
            $table->string('threshold_operator', 10)->default('>')->after('threshold');
            $table->string('disk_mount_point')->nullable()->after('threshold_operator');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['metric_type', 'threshold', 'threshold_operator', 'disk_mount_point']);
        });
    }
};
