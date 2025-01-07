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
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('assessment_type_id')->constrained();
            $table->foreignId('class_room_id')->constrained();
            $table->foreignId('academic_session_id')->constrained();
            $table->foreignId('term_id')->constrained();
            $table->boolean('is_published')->default(true);
            $table->timestamp('assessment_date')->nullable();
            $table->decimal('score', 5, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('modified_by')->nullable()->constrained('users');
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            // Prevent duplicate grades for same student and assessment
            $table->unique(['student_id', 'subject_id', 'assessment_type_id', 'academic_session_id', 'term_id'], 'unique_student_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
