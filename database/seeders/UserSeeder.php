<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        // Development logins: the password is the same as the username.
        foreach (['admin', 'cashier', 'rider'] as $role) {
            User::create([
                'username' => $role,
                'password' => Hash::make($role),
                'role' => $role,
            ]);
        }
    }
}
