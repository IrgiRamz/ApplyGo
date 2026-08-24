<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun Admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123!'),
            'role' => 'admin',
        ]);

        // Akun User Biasa
        User::create([
            'name' => 'Irgi',
            'email' => 'irgi@example.com',
            'password' => Hash::make('irgi123!'),
            'role' => 'user',
        ]);
    }
}
