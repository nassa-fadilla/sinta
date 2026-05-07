<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jika ingin generate user dummy pakai factory
        // User::factory(10)->create();

        // Jalankan seeder AdminUserSeeder
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}