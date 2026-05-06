<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_issues', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('error_projects')->cascadeOnDelete();
            $table->char('fingerprint_hash', 40);
            $table->string('title');
            $table->string('culprit')->nullable();
            $table->string('platform')->nullable();
            $table->string('level')->default('error');
            $table->string('status')->default('open');
            $table->unsignedInteger('times_seen')->default(0);
            $table->unsignedInteger('users_seen')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('ignored_until')->nullable();
            $table->ulid('latest_event_id')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'fingerprint_hash']);
            $table->index(['project_id', 'status', 'last_seen_at']);
            $table->index(['project_id', 'level']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_issues');
    }
};
