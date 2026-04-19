<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name'              => 'Admin',
            'email'             => 'admin@brgy-caranas.gov.ph',
            'password'          => bcrypt('Admin@1234'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name'              => 'Maria Clerk',
            'email'             => 'clerk@brgy-caranas.gov.ph',
            'password'          => bcrypt('Clerk@1234'),
            'role'              => 'staff',
            'email_verified_at' => now(),
        ]);
    }
}
