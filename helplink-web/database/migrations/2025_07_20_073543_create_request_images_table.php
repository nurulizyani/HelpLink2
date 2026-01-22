<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('request_id');
            $table->string('image_path');
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_images');
    }
};
