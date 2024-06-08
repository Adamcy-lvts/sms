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
        Schema::create('subs_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade'); // ID of the school paying the subscription
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // The amount of the payment
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->decimal('split_amount_agent', 10, 2)->nullable();
            $table->string('split_code')->nullable();
            $table->string('status'); // Status of the payment (e.g., pending, completed, failed)
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade'); // Method used for the payment (e.g., credit card, bank transfer)
            $table->string('reference')->unique(); // A unique identifier provided by the payment gateway
            $table->dateTime('date'); // The date and time the payment was processed
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subs_payments');
    }
};
