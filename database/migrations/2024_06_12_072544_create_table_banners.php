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
        Schema::Create('banners', function (Blueprint $table) {
            $table->id();
            $table->longtext('page_name');
            $table->longtext('page_tittle')->nullable();
            $table->string('image')->nullable();
            $table->longtext('description')->nullable();
            $table->longtext('additional_info')->nullable();
            $table->longtext('faqs')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropIfExists('banners');
        });
    }
};
