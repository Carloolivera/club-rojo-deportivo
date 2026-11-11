<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'overdue', 'exempt'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->enum('payment_method', ['mp', 'manual', 'transfer', 'cash'])->nullable();
            $table->string('mp_preference_id')->nullable();
            $table->string('mp_payment_id')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('member_id');
            $table->index('status');
            $table->index('period');
            $table->unique(['member_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
