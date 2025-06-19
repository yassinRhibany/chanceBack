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
        Schema::table('factories', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            // $table->foreign('opprtuntiy_image_id')->references('id')->on('opprtunity_images')->onDelete('cascade');
            
        });
    
        Schema::table('investment_opprtunities', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('factory_id')->references('id')->on('factories')->onDelete('cascade');
        });
    
        Schema::table('investments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('opprtunty_id')->references('id')->on('investment_opprtunities')->onDelete('cascade');
           
        });

        Schema::table('investment_offers', function (Blueprint $table) {
            $table->foreign('investment_id')->references('id')->on('investment_opprtunities')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            
        });
        
        Schema::table('returns', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('opprtunty_id')->references('id')->on('investment_opprtunities')->onDelete('cascade');
        });

        Schema::table('opprtunity_images', function (Blueprint $table) {
            $table->foreign('factory_id')->references('id')->on('factories')->onDelete('cascade');
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['category_id']);
           // $table->dropForeign(['opprtuntiy_image_id']);
            
        });
    
        

        Schema::table('investments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['opprtunty_id']);
           
        });

        Schema::table('investment_offers', function (Blueprint $table) {
            $table->dropForeign(['investment_id']);
            $table->dropForeign(['seller_id']);
            $table->dropForeign(['buyer_id']);
        });
        Schema::table('returns', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['opprtunty_id']);
        });

        Schema::table('investment_opprtunities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['factory_id']);
        });
        // Schema::table('opprtunity_images', function (Blueprint $table) {
        //     $table->dropForeign(['factory_id']);
        // });
    }
};
