<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE requests MODIFY status ENUM('pending', 'approved', 'rejected', 'claimed', 'completed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE requests MODIFY status ENUM('pending', 'approved', 'rejected', 'claimed') DEFAULT 'pending'");
    }
};

