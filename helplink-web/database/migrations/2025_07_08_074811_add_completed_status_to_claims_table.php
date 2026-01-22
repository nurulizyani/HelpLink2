<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- jangan lupa import DB

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE claims MODIFY status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE claims MODIFY status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};