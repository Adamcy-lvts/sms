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
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit')->default('piece'); // piece, rim, box, etc.
            $table->text('description')->nullable();
            $table->decimal('default_amount', 10, 2)->default(0); // Unit price
            $table->boolean('is_stock_tracked')->default(false);
            $table->integer('current_stock')->default(0);
            $table->integer('minimum_quantity')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->enum('frequency', ['monthly', 'termly', 'yearly'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('specifications')->nullable();
            $table->timestamp('last_purchase_date')->nullable();
            $table->decimal('last_purchase_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'expense_category_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
