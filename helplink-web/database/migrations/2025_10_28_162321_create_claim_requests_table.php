<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('claim_requests', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedInteger('request_id'); // FK ke requests.id
            $table->unsignedInteger('user_id'); // FK ke users.id
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            // 🔧 Foreign Keys
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['request_id', 'user_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('claim_requests');
    }
};
