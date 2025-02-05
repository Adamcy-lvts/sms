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
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id');
            $table->string('grade');        // e.g., A, B, C
            $table->integer('min_score');   // Minimum score for this grade
            $table->integer('max_score');   // Maximum score for this grade
            $table->string('remark')->nullable(); // e.g., Excellent, Good, Fair
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Prevent overlapping grade ranges
            $table->unique(['school_id', 'grade']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
    }
};
