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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('slug');
            $table->foreignId('school_id');
            $table->integer('position')->default(0)->nullable();
            $table->string('color')->default('#000000')->nullable();
            $table->string('icon')->default('fa-book');
            $table->string('description')->nullable();
            $table->string('description_ar')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->softDeletes();

            $table->unique(['name', 'school_id']);
            $table->unique(['slug', 'school_id']);
            $table->index(['name', 'school_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
