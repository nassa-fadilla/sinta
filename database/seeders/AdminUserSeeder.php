<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sman2.sch.id'],
            [
                'name' => 'Admin SINTA',
                'password' => Hash::make('rahasia123'),
                'role' => 'admin',
            ]
        );
    }
}