<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->foreignUlid('server_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('metric_type')->nullable()->after('server_id');
            $table->decimal('threshold', 10, 2)->nullable()->after('server_metric_type');
            $table->string('threshold_operator', 10)->default('>')->after('threshold');
            $table->string('disk_mount_point')->nullable()->after('threshold_operator');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            $table->dropColumn(['server_id', 'server_metric_type', 'threshold', 'threshold_operator', 'disk_mount_point']);
        });
    }
};
