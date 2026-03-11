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
        Schema::create('soundcloud_formats', function (Blueprint $table) {
            $table->id();
            $table->string('format_id');
            $table->string('url');
            $table->string('protocol');
            $table->integer('quality');
            $table->bigInteger('filesize_approx');
            $table->string('format');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soundcloud_formats');
    }
};
