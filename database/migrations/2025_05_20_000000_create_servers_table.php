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
        Schema::create('servers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address');
            $table->string('os')->nullable();
            $table->string('secret', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'last_seen_at']);
            $table->index('hostname');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};