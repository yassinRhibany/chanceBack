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
        Schema::create('investment_opprtunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->decimal('target_amount');
            $table->decimal('collected_amount');
            $table->foreignId('factory_id');
            $table->decimal('minimum_target')->nullable();
            $table->date('strtup')->nullable();
            $table->string('payout_frequency');
            $table->decimal('profit_percentage');
            $table->string('descrption')->nullable();

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_opprtunities');
    }
};
