<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // panjang token FCM boleh mencecah ~163 chars; guna text jika mahu selamat
            $table->string('fcm_token', 255)->nullable()->after('remember_token');
            $table->index('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['fcm_token']);
            $table->dropColumn('fcm_token');
        });
    }
};
