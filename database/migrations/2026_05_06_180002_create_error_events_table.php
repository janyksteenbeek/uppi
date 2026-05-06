<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('issue_id')->constrained('error_issues')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('error_projects')->cascadeOnDelete();
            $table->char('event_id', 32)->unique();
            $table->string('level')->default('error');
            $table->string('platform')->nullable();
            $table->string('environment')->nullable();
            $table->string('release')->nullable();
            $table->string('server_name')->nullable();
            $table->string('transaction')->nullable();
            $table->text('message')->nullable();
            $table->string('culprit')->nullable();
            $table->json('exception')->nullable();
            $table->json('stacktrace')->nullable();
            $table->json('breadcrumbs')->nullable();
            $table->json('tags')->nullable();
            $table->json('contexts')->nullable();
            $table->json('request')->nullable();
            $table->json('user_context')->nullable();
            $table->json('extra')->nullable();
            $table->json('sdk')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['issue_id', 'occurred_at']);
            $table->index(['project_id', 'occurred_at']);
            $table->index('environment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_events');
    }
};
