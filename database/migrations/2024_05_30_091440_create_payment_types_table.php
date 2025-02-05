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
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id');
            $table->string('name');  // Name of the payment type, e.g., Tuition, Registration Fee, Donation
            $table->enum('category', [
                'service_fee',    // For non-physical like tuition, lab fees etc
                'physical_item'   // For items that need inventory tracking
            ])->default('service_fee');
            $table->decimal('amount', 10, 2);
            $table->boolean('active')->default(true);
            $table->boolean('is_tuition')->default(false);
            $table->string('class_level')->nullable();
            $table->boolean('installment_allowed')->default(false);
            $table->decimal('min_installment_amount', 10, 2)->nullable();
            $table->boolean('has_due_date')->default(false);
            $table->text('description')->nullable();  // Additional details about the payment type
            $table->softDeletes();
            $table->timestamps();

            // $table->unique('school_id', 'name');
            $table->unique(['school_id', 'name']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_types');
    }
};
