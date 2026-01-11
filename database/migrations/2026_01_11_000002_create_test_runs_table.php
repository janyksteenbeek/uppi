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
        Schema::create('test_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('test_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(TestStatus::PENDING->value);
            $table->integer('duration_ms')->nullable()->comment('Total test duration in milliseconds');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_runs');
    }
};
