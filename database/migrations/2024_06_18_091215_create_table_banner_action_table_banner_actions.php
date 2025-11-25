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
        Schema::Create('banner_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('banner_id')->nullable()->index();
            $table->foreign('banner_id')->references('id')->on('banners')->onDelete('cascade');
            $table->string('target')->nullable();
            $table->longText('link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('banner_actions');
    }
};
