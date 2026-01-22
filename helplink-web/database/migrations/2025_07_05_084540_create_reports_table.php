<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id(); // ini ok
            $table->unsignedInteger('reported_user_id'); // ubah kepada unsignedInteger
            $table->unsignedInteger('reporter_id');       // ubah juga
            $table->text('reason');
            $table->timestamps();

            $table->foreign('reported_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
