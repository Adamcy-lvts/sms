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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name');
            $table->string('account_number');
            $table->string('account_name');
            $table->foreignId('bank_id')->constrained()->onDelete('cascade');
            $table->string('referral_code')->unique()->nullable();
            $table->string('subaccount_code')->nullable();
            $table->decimal('percentage', 5, 2)->nullable()->default(20); // e.g., 10.00 for 10%
            $table->decimal('fixed_rate', 10, 2)->nullable()->default(500);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
