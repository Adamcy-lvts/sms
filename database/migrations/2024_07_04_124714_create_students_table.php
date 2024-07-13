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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('identification_number')->nullable();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_room_id')->constrained('class_rooms');
            $table->foreignId('user_id')->constrained()->nullable();
            $table->foreignId('admission_id')->constrained('admissions')->nullable(); 
            $table->foreignId('status_id')->constrained();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('full_name')->virtualAs('concat(first_name, \' \', middle_name, \' \', last_name)');
            $table->date('date_of_birth');
            $table->string('phone_number')->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
