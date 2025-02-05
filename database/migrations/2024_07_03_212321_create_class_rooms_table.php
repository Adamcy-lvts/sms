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
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('level')->nullable(); // nursery, primary, secondary etc
            $table->foreignId('school_id');
            $table->integer('capacity')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['name', 'school_id']);
            $table->unique(['slug', 'school_id']);
            $table->index(['name', 'school_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_rooms');
    }
};
