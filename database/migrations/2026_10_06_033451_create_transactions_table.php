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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->decimal('total', 10,2);
            $table->enum('type', ['WALK IN', 'DELIVERY'])->default('WALK IN');
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED', 'REFUNDED'])->default('PENDING');
            $table->enum('payment_status', ['PENDING', 'PAID', 'CANCELLED', 'REFUNDED'])->default('PENDING');
            $table->timestamp('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
