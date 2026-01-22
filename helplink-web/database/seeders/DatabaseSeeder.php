<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\AdminSeeder; // 🟡 Tambah ini

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 🔥 Panggil AdminSeeder
        $this->call(AdminSeeder::class);
    }
}
