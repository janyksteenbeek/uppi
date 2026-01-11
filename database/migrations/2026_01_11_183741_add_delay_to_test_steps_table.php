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
        Schema::table('test_steps', function (Blueprint $table) {
            $table->unsignedInteger('delay_ms')->nullable()->after('selector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_steps', function (Blueprint $table) {
            $table->dropColumn('delay_ms');
        });
    }
};
