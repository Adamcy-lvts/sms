<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_payment_id')->nullable()->constrained('payments')->nullOnDelete();      
            $table->boolean('is_balance_payment')->default(false);
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('payment_method_id')->constrained()->onDelete('restrict');
            $table->foreignId('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('status_id')->constrained()->onDelete('restrict');
            $table->foreignId('academic_session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('term_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_period')->nullable();
            $table->string('reference')->unique()->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_phone_number')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('deposit', 10, 2)->nullable();
            $table->decimal('balance', 10, 2)->nullable();
            $table->json('meta_data')->nullable();
            $table->text('remark')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();

            $table->index([ 'student_id', 'term_id','class_room_id']);
              $table->index(['original_payment_id', 'is_balance_payment']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
