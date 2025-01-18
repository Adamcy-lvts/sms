<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->morphs('statusable'); // This allows the table to track status changes for any model
            $table->foreignId('from_status_id')->nullable()->constrained('statuses');
            $table->foreignId('to_status_id')->constrained('statuses');
            $table->text('reason');
            $table->json('metadata')->nullable(); // For any additional data
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_changes');
    }
};
