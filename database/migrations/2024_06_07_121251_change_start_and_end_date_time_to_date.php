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

            $table->date('start_date_time')->nullable()->change();
            $table->date('end_date_time')->nullable()->change();

            $table->renameColumn('start_date_time', 'start_date');
            $table->renameColumn('end_date_time', 'end_date');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            $table->timestamp('start_date')->nullable()->change();
            $table->timestamp('end_date')->nullable()->change();

            $table->renameColumn('start_date', 'start_date_time');
            $table->renameColumn('end_date', 'end_date_time');
        });

    }
};
