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
        Schema::create('school_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['holiday', 'event', 'break']);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // yearly, termly, monthly
            $table->boolean('excludes_attendance')->default(false);
            $table->string('color')->nullable();
            $table->json('excluded_dates')->nullable(); // For recurring events exceptions
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_calender_events');
    }
};
