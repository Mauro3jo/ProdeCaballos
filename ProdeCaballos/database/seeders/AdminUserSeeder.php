<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'mauro_3jo@hotmail.com'],
            [
                'name' => 'Mauro Nicolas Trejo',
                'password' => Hash::make('Carp2023'),
            ]
        );
    }
}
