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
        Schema::create('template_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable();
            $table->string('name');
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('field_type');
            $table->string('sample_value')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('mapping')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Add composite unique index for name and school_id
            $table->unique(['school_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_variables');
    }
};
