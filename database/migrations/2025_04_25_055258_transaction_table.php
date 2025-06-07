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
        Schema::create('transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('stripe_payment_intent_id')->unique();
            $table->string('currency', 10)->default('usd');
            $table->enum('type', ['deposit', 'withdrawal', 'sell', 'buy', 'return']);
            $table->decimal('amount');
            $table->date('time_operation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction');
    }
};
