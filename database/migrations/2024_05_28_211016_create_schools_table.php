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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Unique school name
            $table->string('slug')->unique(); // Unique slug for URL identification
            $table->string('email')->unique(); // Unique email address
            $table->string('customer_code')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            $table->text('address'); // School address
            $table->string('phone'); // School phone number
            $table->string('logo')->nullable(); // Path to the logo image, can be null if no logo is uploaded
            $table->json('settings')->nullable(); // JSON column for storing additional settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
