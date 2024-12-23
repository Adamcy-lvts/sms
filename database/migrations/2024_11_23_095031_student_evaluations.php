<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store activity types (e.g., Sports, Music, Art, etc.)
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // e.g., 'Sports', 'Arts', 'Academic'
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(true);
            $table->integer('display_order')->default(0);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();

            // Ensure unique names within each school
            $table->unique(['school_id', 'name']);
        });

        // Store behavioral traits (e.g., Punctuality, Neatness, etc.)
        Schema::create('behavioral_traits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // e.g., 'Social', 'Emotional', 'Learning'
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->float('weight')->default(1.0);
            $table->boolean('is_default')->default(true);
            $table->timestamps();
            // Ensure unique names within each school
            $table->unique(['school_id', 'name']);
        });

        // Store student term activities
        Schema::create('student_term_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_type_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->comment('1-5 star rating');
            $table->string('remark')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            // Ensure unique activities per student per term
            $table->unique([
                'student_id',
                'academic_session_id',
                'term_id',
                'activity_type_id'
            ], 'unique_student_term_activity');
        });

        // Store student term behavioral traits
        Schema::create('student_term_traits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            $table->foreignId('behavioral_trait_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->comment('1-5 star rating');
            $table->string('remark')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            // Ensure unique traits per student per term
            $table->unique([
                'student_id',
                'academic_session_id',
                'term_id',
                'behavioral_trait_id'
            ], 'unique_student_term_trait');
        });

        // Store term comments from teachers and principal
        Schema::create('student_term_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            $table->text('class_teacher_comment')->nullable();
            $table->foreignId('class_teacher_id')->nullable()->constrained('users');
            $table->text('principal_comment')->nullable();
            $table->foreignId('principal_id')->nullable()->constrained('users');
            $table->timestamps();

            // Ensure one comment record per student per term
            $table->unique([
                'student_id',
                'academic_session_id',
                'term_id'
            ], 'unique_student_term_comment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_term_comments');
        Schema::dropIfExists('student_term_traits');
        Schema::dropIfExists('student_term_activities');
        Schema::dropIfExists('behavioral_traits');
        Schema::dropIfExists('activity_types');
    }
};
