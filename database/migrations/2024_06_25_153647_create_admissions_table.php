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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->string('session')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('full_name')->virtualAs('concat(first_name, \' \', middle_name, \' \', last_name)');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('address');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->foreignId('state_id')->constrained()->onDelete('cascade')->nullable();
            $table->foreignId('lga_id')->constrained()->onDelete('cascade')->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('genotype')->nullable();
            $table->string('disability_type')->nullable();
            $table->string('disability_description')->nullable();
            $table->string('previous_school_name')->nullable();
            $table->string('previous_class')->nullable();
            $table->date('application_date')->nullable();
            $table->date('admitted_date')->nullable();
            $table->string('admission_number')->unique()->nullable();
            $table->string('class_room_id')->nullable();
            $table->string('passport_photograph')->nullable();
            $table->foreignId('status_id')->constrained()->onDelete('cascade');

             // Guardian/Parent Information
             $table->string('guardian_name');
             $table->string('guardian_relationship');
             $table->string('guardian_phone_number');
             $table->string('guardian_email')->nullable();
             $table->string('guardian_address')->nullable();
 
             // Emergency Contact Information
             $table->string('emergency_contact_name');
             $table->string('emergency_contact_relationship');
             $table->string('emergency_contact_phone_number');
             $table->string('emergency_contact_email')->nullable();


            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
