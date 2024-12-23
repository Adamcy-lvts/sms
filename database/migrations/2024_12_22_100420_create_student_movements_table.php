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
        Schema::create('student_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('from_class_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignId('to_class_id')->nullable()->constrained('class_rooms')->onDelete('cascade');
            $table->foreignId('from_session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('to_session_id')->nullable()->constrained('academic_sessions')->onDelete('cascade');
            $table->enum('movement_type', [
                'promotion',      // Moving up to next class
                'demotion',      // Moving down to lower class
                'transfer',      // Moving to another school
                'withdrawal',    // Voluntary withdrawal
                'graduation',    // Completing studies
                'repeat',        // Repeating same class
            ]);
            $table->date('movement_date');
            $table->text('reason')->nullable();
            $table->json('academic_performance')->nullable(); // Store grades/performance
            $table->boolean('requires_new_admission')->default(false); // For transfers/new sessions
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_movements');
    }
};
