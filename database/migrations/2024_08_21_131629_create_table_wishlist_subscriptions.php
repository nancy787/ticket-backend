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
        Schema::create('wishlist_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('continent_id')->index()->nullable();
            $table->foreign('continent_id')->references('id')->on('continents')->onDelete('cascade');

            $table->unsignedBigInteger('country_id')->index()->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');

            $table->unsignedBigInteger('event_id')->index()->nullable();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');

            $table->json('category_ids')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishlist_subscriptions', function (Blueprint $table) {
            $table->dropIfExists('wishlist_subscriptions');
        });
    }
};
