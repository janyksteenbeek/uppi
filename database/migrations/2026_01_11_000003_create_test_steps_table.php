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
        Schema::create('test_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('test_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('type');
            $table->text('value')->nullable();
            $table->string('selector')->nullable();
            $table->timestamps();

            $table->index(['test_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_steps');
    }
};
