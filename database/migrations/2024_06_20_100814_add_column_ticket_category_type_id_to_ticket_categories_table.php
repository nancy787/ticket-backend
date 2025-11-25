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
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_category_type_id')->nullable()->index()->after('id');
            $table->foreign('ticket_category_type_id')->references('id')->on('ticket_category_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->dropForeign(['ticket_category_type_id']);
            $table->dropColumn('ticket_category_type_id');
        });
    }
};
