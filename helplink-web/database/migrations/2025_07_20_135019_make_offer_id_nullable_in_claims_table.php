<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['offer_id']); // buang foreign key lama
            $table->unsignedBigInteger('offer_id')->nullable()->change(); // jadikan nullable
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade'); // tambah balik FK
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['offer_id']);
            $table->unsignedBigInteger('offer_id')->nullable(false)->change();
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade');
        });
    }
};

