<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('conversations', function (Blueprint $table) {
    $table->id();

    // USERS (INT UNSIGNED)
    $table->unsignedInteger('user1_id');
    $table->unsignedInteger('user2_id');

    // CONTEXT
    $table->unsignedBigInteger('offer_id')->nullable();
    $table->unsignedBigInteger('request_id')->nullable();

    $table->string('last_message')->nullable();
    $table->timestamps();

    // FOREIGN KEYS
    $table->foreign('user1_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('user2_id')->references('id')->on('users')->onDelete('cascade');
});

}

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
