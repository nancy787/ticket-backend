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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('continent_id')->nullable()->index()->after('currency');
            $table->foreign('continent_id')->references('id')->on('continents')->onDelete('cascade');
            $table->unsignedBigInteger('country_id')->nullable()->index()->after('continent_id');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['continent_id']);
            $table->dropForeign(['country_id']);
            $table->dropColumn('continent_id');
            $table->dropColumn('country_id');
        });
    }
};
