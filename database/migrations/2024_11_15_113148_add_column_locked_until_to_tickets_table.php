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
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('locked_until')->nullable();
            $table->unsignedBigInteger('locked_by_user_id')->nullable()->index();
            $table->foreign('locked_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['locked_by_user_id']);
            $table->dropColumn('locked_until');
            $table->dropColumn('locked_by_user_id');
        });
    }
};
