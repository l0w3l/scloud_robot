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
        Schema::create('soundcloud_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('file_id')->nullable();
            $table->bigInteger('soundcloud_id')->unique();
            $table->string('uploader');
            $table->integer('uploader_id');
            $table->timestamp('timestamp');
            $table->string('title');
            $table->string('track');
            $table->text('description')->nullable();
            $table->float('duration');
            $table->string('page_url');
            $table->string('short_url')->nullable();
            $table->json('genres')->nullable();
            $table->json('tags')->nullable();
            $table->json('artists')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soundcloud_tracks');
    }
};
