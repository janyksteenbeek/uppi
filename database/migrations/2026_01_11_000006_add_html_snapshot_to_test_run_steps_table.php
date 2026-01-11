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
        Schema::table('test_run_steps', function (Blueprint $table) {
            $table->longText('html_snapshot')->nullable()->after('screenshot_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_run_steps', function (Blueprint $table) {
            $table->dropColumn('html_snapshot');
        });
    }
};
