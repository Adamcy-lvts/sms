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
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->json('employee_settings')->nullable();
            $table->json('academic_settings')->nullable();
            $table->json('admission_settings')->nullable();
            $table->json('payment_settings')->nullable();
            $table->boolean('enable_arabic')->default(false);
            $table->string('rtl_direction')->default('ltr');

            $table->timestamps();

            $table->unique('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
