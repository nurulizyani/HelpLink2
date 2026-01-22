<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
{
    Schema::create('offers', function (Blueprint $table) {
        $table->id('offer_id');
        $table->unsignedInteger('user_id');
        $table->string('item_name');
        $table->text('description')->nullable();
        $table->integer('quantity')->nullable();
        $table->string('address')->nullable();
        $table->enum('delivery_type', ['pickup', 'delivery'])->nullable();
        $table->string('status')->default('available'); // available, claimed, completed
        $table->tinyInteger('rating')->nullable(); // 1-5 stars
        $table->text('comment')->nullable();
        $table->timestamps();

        // Foreign key
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};