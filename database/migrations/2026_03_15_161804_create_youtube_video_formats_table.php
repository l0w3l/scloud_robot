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
        Schema::create('youtube_video_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youtube_video_id')->constrained()->cascadeOnDelete();
            $table->string('format_id');
            $table->string('file_id')->nullable();
            $table->unsignedBigInteger('filesize')->nullable();
            $table->unsignedBigInteger('width')->nullable();
            $table->unsignedBigInteger('height')->nullable();
            $table->string('resolution')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_video_formats');
    }
};
