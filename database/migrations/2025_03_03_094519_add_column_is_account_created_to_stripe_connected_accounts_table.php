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
        Schema::table('stripe_connect_accounts', function (Blueprint $table) {
            $table->boolean('is_created')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_connect_accounts', function (Blueprint $table) {
            $table->dropColumn('stripe_connect_accounts');
        });
    }
};
