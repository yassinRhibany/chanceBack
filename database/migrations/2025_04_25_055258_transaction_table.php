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
              $table->foreignId('opprtunty_id');
            $table->enum('type', ['deposit', 'withdrawal', 'sell', 'buy', 'return']);
            $table->decimal('amount');
            $table->date('time_operation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction');
         Schema::table('returns', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
        $table->dropForeign(['opprtunty_id']);
        $table->dropColumn('opprtunty_id');
    });
    }
};
