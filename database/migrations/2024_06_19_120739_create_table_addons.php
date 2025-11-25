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
        Schema::Create('addons', function (Blueprint $table) {
            $table->id();
            $table->boolean('photo_pack')->default(0);
            $table->boolean('race_with_friend')->default(0);
            $table->boolean('spectator')->default(0);
            $table->boolean('charity_ticket')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            Schema::dropIfExists('addons');
        });
    }
};
