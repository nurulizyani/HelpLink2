<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            // buang column lama
            $table->dropColumn(['quantity', 'location', 'delivery_type']);

            // tambah column baru
            $table->string('image')->nullable()->after('longitude');
            $table->text('ocr_text')->nullable()->after('document');

            // ubah enum
            $table->enum('status', ['pending','approved','rejected','fulfilled'])
                ->default('pending')
                ->change();

            $table->string('category', 100)->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->integer('quantity')->nullable();
            $table->string('location', 255)->nullable();
            $table->enum('delivery_type', ['pickup','delivery'])->nullable();

            $table->dropColumn(['image', 'ocr_text']);
        });
    }
};
