<?php

use App\Enums\Tests\TestStatus;
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
        Schema::create('test_run_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('test_run_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('test_step_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('status')->default(TestStatus::PENDING->value);
            $table->text('error_message')->nullable();
            $table->text('screenshot_path')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['test_run_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_run_steps');
    }
};
