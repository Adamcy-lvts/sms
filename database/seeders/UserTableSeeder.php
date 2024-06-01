<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::count() == 0) {
            $user = User::create([
                'first_name'          => 'Adam',
                'last_name'          => 'Mohammed',
                'email'             => 'lv4mj1@gmail.com',
                'password'          => Hash::make('password'),
                'remember_token'    => Str::random(60),

            ]);

            // $user->assignRole('super_admin');
        }
    }
}
