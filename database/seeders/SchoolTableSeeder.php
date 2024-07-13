<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SchoolTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (School::count() > 0) {
            return;
        }
        // Create a new school or find an existing one
        $school1 = School::firstOrCreate([
            'name' => 'Kings Private School',
            'slug' => Str::slug('Kings Private School'),
            'email' => 'kings@mail.com',
            'customer_code' => null,
            'address' => 'Lagos Street, Maiduguri City',
            'phone' => '09012345678',
            'logo' => null,
            'settings' => json_encode(['theme' => 'default']),
        ]);

        // Create a new user
        $user1 = User::create([
            'first_name'          => 'Sadik',
            'last_name'          => 'Ahmed',
            'email'             => 'kings@mail.com',
            'password'          => Hash::make('password123'),
            'remember_token'    => Str::random(60),

        ]);

        // Attach the user to the school
        $school1->members()->attach($user1->id);

          // Create a new school or find an existing one
          $school2 = School::firstOrCreate([
            'name' => 'Khalil Integrated Academy',
            'slug' => Str::slug('Khalil Integrated Academy'),
            'email' => 'Kia@mail.com',
            'customer_code' => null,
            'address' => 'Lagos Street, Maiduguri City',
            'phone' => '08032145678',
            'logo' => null,
            'settings' => json_encode(['theme' => 'default']),
        ]);

        // Create a new user
        $user2 = User::create([
            'first_name'        => 'Abba ',
            'last_name'         => 'Mohammed',
            'email'             => 'Kia@mail.com',
            'password'          => Hash::make('password123'),
            'remember_token'    => Str::random(60),

        ]);

        // Attach the user to the school
        $school2->members()->attach($user2->id);
    }
}
