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
            $table->foreignId('school_id')->on('schools')->onDelete('cascade'); // ID of the school paying the subscription
            $table->decimal('amount', 10, 2); // The amount of the payment
            $table->string('status'); // Status of the payment (e.g., pending, completed, failed)
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade'); // Method used for the payment (e.g., credit card, bank transfer)
            $table->date('start_date'); // Start date of the subscription period
            $table->date('end_date')->nullable(); // End date of the subscription period, if applicable
            $table->string('reference')->unique(); // A unique identifier provided by the payment gateway
            $table->dateTime('date'); // The date and time the payment was processed
            $table->date('next_billing_date')->nullable(); // Date for the next billing cycle
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
