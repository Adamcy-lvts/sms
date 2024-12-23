<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main template table
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->json('header_config')->nullable(); // School name, logo, address format
            $table->json('student_info_config')->nullable(); // Which student fields to display
            $table->json('grade_table_config')->nullable(); // CA columns, exam columns, etc
            $table->json('comments_config')->nullable();
            $table->json('activities_config')->nullable();
            $table->json('rtl_config')->nullable();
            $table->json('print_config')->nullable();
            $table->json('style_config')->nullable(); // Colors, fonts, borders etc
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique template names per school
            $table->unique(['school_id', 'name']);
        });

        // Sections for organizing template content
        Schema::create('report_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // header, student_info, grades_table, summary, etc
            $table->integer('order');
            $table->json('config')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        // CA (Continuous Assessment) configuration
        Schema::create('report_assessment_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "CA 1", "CA 2", "Exam"
            $table->string('key'); // e.g., "ca1", "ca2", "exam"
            $table->integer('max_score')->default(100);
            $table->integer('weight')->default(0); // Percentage weight in final calculation
            $table->integer('order');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        // Grading scale configuration
        Schema::create('report_grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->constrained()->onDelete('cascade');
            $table->string('grade'); // e.g., "A", "B", "C"
            $table->integer('min_score');
            $table->integer('max_score');
            $table->string('remark')->nullable(); // e.g., "Excellent", "Good", "Fair"
            $table->string('color_code')->nullable(); // For visual representation
            $table->timestamps();
        });

        // Comments section configuration
        Schema::create('report_comment_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Class Teacher's Comment", "Principal's Comment"
            $table->string('type'); // text, predefined, rating
            $table->json('options')->nullable(); // For predefined comments or rating scales
            $table->integer('order');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_comment_sections');
        Schema::dropIfExists('report_grading_scales');
        Schema::dropIfExists('report_assessment_columns');
        Schema::dropIfExists('report_sections');
        Schema::dropIfExists('report_templates');
    }
};