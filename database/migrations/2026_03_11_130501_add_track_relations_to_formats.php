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
        Schema::table('soundcloud_formats', function (Blueprint $table) {
            $table->foreignId('track_id')->constrained('soundcloud_tracks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soundcloud_formats', function (Blueprint $table) {
            $table->dropForeignIdFor('soundcloud_tracks');
        });
    }
};
