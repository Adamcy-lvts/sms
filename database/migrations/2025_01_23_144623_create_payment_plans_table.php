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
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained();
            $table->foreignId('payment_type_id')->constrained();
            $table->string('name');
            $table->enum('class_level', ['nursery', 'primary', 'secondary']);
            $table->decimal('session_amount', 10, 2);  // Keep explicit session amount
            $table->decimal('term_amount', 10, 2);     // Keep explicit term amount
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
