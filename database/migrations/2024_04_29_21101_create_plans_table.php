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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            // Basic Plan Information
            $table->string('name');
            $table->decimal('price', 10, 2); // Supports up to 99,999,999.99
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('interval')->default('monthly'); // monthly or yearly
            $table->integer('duration')->default(30); // Duration in days

            // Features and Configuration
           
            $table->string('plan_code')->nullable(); // Paystack plan code
            $table->integer('yearly_discount')->default(0); // Percentage discount for yearly plans

            // Plan Status and Display
            $table->string('status')->default('active'); // active, inactive, archived
            $table->integer('max_students')->nullable();
            $table->integer('max_staff')->nullable();
            $table->integer('max_classes')->nullable();
            $table->string('badge_color')->nullable(); // For UI customization
            $table->string('cto')->nullable()->default('Purchase'); // Call to action text

            // Trial Settings
            $table->integer('trial_period')->default(0); // Trial period in days
            $table->boolean('has_trial')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('interval');
            $table->unique('plan_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
