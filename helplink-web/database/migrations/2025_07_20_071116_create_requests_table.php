<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('item_name');
            $table->integer('quantity')->nullable();
            $table->text('description')->nullable(); // Jalan cerita kenapa mohon
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('delivery_type', ['pickup', 'delivery'])->nullable();

            // Dokumen sokongan contoh slip gaji / surat pengesahan
            $table->json('supporting_documents')->nullable(); // Untuk simpan banyak nama fail

            // Status admin semak: pending -> approved / rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Optional rating & komen dari pemberi bantuan
            $table->integer('rating')->nullable(); // 1–5
            $table->text('comment')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
