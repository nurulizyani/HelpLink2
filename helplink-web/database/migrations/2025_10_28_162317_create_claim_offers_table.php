<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('claim_offers', function (Blueprint $table) {
            $table->id(); // ini akan cipta column 'id' sebagai PK untuk table claim_offers
            $table->unsignedBigInteger('offer_id'); // FK ke table offers
            $table->unsignedInteger('user_id');  // FK ke table users
            $table->string('status')->default('pending'); // pending|approved|rejected|completed
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Elak double claim
            $table->unique(['offer_id', 'user_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('claim_offers');
    }
};
