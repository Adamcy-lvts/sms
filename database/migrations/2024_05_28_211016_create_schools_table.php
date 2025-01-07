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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            // Basic Information
            $table->string('name')->unique();
            $table->string('name_ar')->nullable()->unique();
            $table->string('slug')->unique();

            // Contact Information
            $table->string('email')->unique();
            $table->string('phone');
            $table->text('address');
            $table->string('website')->nullable();

            // Location Details
            // Location - Move foreign keys to after table creation
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('lga_id')->nullable();
            $table->string('postal_code')->nullable();

            // School Details
            $table->enum('school_type', ['primary', 'secondary', 'both'])->default('both');
            $table->json('curriculum_types')->nullable(); // ['national', 'islamic', 'international', 'vocational']
            $table->integer('student_capacity')->nullable();
            $table->year('established_year')->nullable();
            $table->string('motto')->nullable();
            $table->text('ownership_type')->nullable();
            $table->text('language_of_instruction')->nullable();
            $table->text('gender_type')->nullable();

            // Media
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();

            // Business Information
            $table->string('registration_number')->nullable()->unique();
            $table->string('tax_id')->nullable()->unique();
            $table->string('customer_code')->nullable()->unique();

            // Subscription & Agent
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            $table->foreignId('current_plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->boolean('is_on_trial')->default(false);
            $table->timestamp('trial_ends_at')->nullable();

            // Academic Configuration
            $table->enum('academic_period', ['standard', 'modified'])->default('standard'); // Sep-Jul or Jan-Dec
            $table->enum('term_structure', ['three_terms', 'two_semesters'])->default('three_terms');

            // Features & Settings
            $table->json('settings')->nullable(); // General settings
            $table->json('features')->nullable(); // Enabled features
            $table->json('configurations')->nullable(); // System configurations
            $table->json('theme_settings')->nullable(); // UI/Theme settings

            // Status & Verification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');

            // Social Media Links
            $table->json('social_links')->nullable(); // Store social media URLs

            // Contact Person
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('contact_person_role')->nullable();

            // Timestamps & Soft Deletes
            $table->timestamps();
            $table->softDeletes();

            // Meta Information
            $table->json('meta_data')->nullable(); // For additional dynamic data
            $table->text('remarks')->nullable();
        });

        // Add foreign key constraints after table creation
        Schema::table('schools', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
            $table->foreign('lga_id')->references('id')->on('lgas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
