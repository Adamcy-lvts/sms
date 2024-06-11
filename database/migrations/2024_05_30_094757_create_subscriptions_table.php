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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('subscription_code')->nullable();
            $table->string('status')->default('active'); // Subscription status: active, cancelled, paused
            $table->timestamp('starts_at'); // The start date of the subscription
            $table->timestamp('ends_at')->nullable(); // The end date of the subscription, if it's not recurring
            $table->timestamp('cancelled_at')->nullable(); // The date when the subscription was cancelled
            $table->timestamp('trial_ends_at')->nullable(); // For handling trial periods
            $table->boolean('is_recurring')->default(false); // Whether the subscription is set to auto-renew
            $table->date('next_payment_date')->nullable();
            $table->json('features')->nullable(); // Features included in the subscription, can store JSON data
            $table->timestamps();

            $table->index('school_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
