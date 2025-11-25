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
        Schema::table('sell_tickets', function (Blueprint $table) {
            $table->json('pdf')->nullable()->change();
            $table->json('link')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_tickets', function (Blueprint $table) {
            $table->string('pdf')->nullable()->change();
            $table->string('link')->nullable()->change();
        });
    }
};
