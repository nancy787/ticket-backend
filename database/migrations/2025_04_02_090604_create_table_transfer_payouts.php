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
        Schema::create('transfer_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('buyer_id')->index();
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('ticket_id')->index();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->unsignedBigInteger('stripe_connected_id')->nullable()->index();
            $table->foreign('stripe_connected_id')->references('id')->on('stripe_connect_accounts')->onDelete('cascade');
            $table->string('destination');
            $table->string('currency', 3);
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_payouts');
    }
};
