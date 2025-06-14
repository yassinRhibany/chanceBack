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
        Schema::create('investment_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id');
            $table->foreignId('seller_id');
            $table->foreignId('buyer_id')->nullable();
            $table->decimal('offred_amount');
            $table->decimal('price');
            $table->boolean('status')->default(0);
            $table->date('sold_at')->nullable();
            $table->timestamps();
        });    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_offers');
    }
};
