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
        Schema::create('soundcloud_thumbnails', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('resolution')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soundcloud_thumbnails');
    }
};
