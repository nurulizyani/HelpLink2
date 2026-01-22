<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
    $table->increments('rating_id');

    // USERS (INT UNSIGNED)
    $table->unsignedInteger('from_user_id');
    $table->unsignedInteger('to_user_id');

    // OFFER / REQUEST
    $table->unsignedInteger('offer_id')->nullable();
    $table->unsignedInteger('request_id')->nullable();

    $table->unsignedTinyInteger('rating_value'); // 1-5
    $table->text('comment')->nullable();

    $table->timestamps();

    // =========================
    // FOREIGN KEYS
    // =========================
    $table->foreign('from_user_id')
        ->references('id')->on('users')
        ->cascadeOnDelete();

    $table->foreign('to_user_id')
        ->references('id')->on('users')
        ->cascadeOnDelete();

    $table->foreign('offer_id')
        ->references('offer_id')->on('offers')
        ->cascadeOnDelete();

    $table->foreign('request_id')
        ->references('id')->on('requests')
        ->cascadeOnDelete();

    // =========================
    // PREVENT DUPLICATE RATING
    // =========================
    $table->unique(
        ['from_user_id', 'to_user_id', 'offer_id'],
        'uniq_rating_offer'
    );

    $table->unique(
        ['from_user_id', 'to_user_id', 'request_id'],
        'uniq_rating_request'
    );
});

    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
