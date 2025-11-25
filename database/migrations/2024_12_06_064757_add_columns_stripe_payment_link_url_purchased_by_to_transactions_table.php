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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('stripe_payment_link_url')->nullable();
            $table->string('stripe_payment_link_id')->nullable();
            $table->unsignedBigInteger('purchased_by')->nullable()->index()->after('id');
            $table->foreign('purchased_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('stripe_payment_link_url');
            $table->dropColumn('stripe_payment_link_id');
            $table->dropForeign(['purchased_by']);
            $table->dropColumn('purchased_by');
        });
    }
};
