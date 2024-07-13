<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StateTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (State::count() == 0) {

            State::insert([
                ['name' => "Abia"],
                ['name' => "Adamawa"],
                ['name' => "Anambra"],
                ['name' => "Akwa Ibom"],
                ['name' => "Bauchi"],
                ['name' => "Bayelsa"],
                ['name' => "Benue"],
                ['name' => "Borno"],
                ['name' => "Cross River"],
                ['name' => "Delta"],
                ['name' => "Ebonyi"],
                ['name' => "Enugu"],
                ['name' => "Edo"],
                ['name' => "Ekiti"],
                ['name' => "FCT - Abuja"],
                ['name' => "Gombe"],
                ['name' => "Imo"],
                ['name' => "Jigawa"],
                ['name' => "Kaduna"],
                ['name' => "Kano"],
                ['name' => "Katsina"],
                ['name' => "Kebbi"],
                ['name' => "Kogi"],
                ['name' => "Kwara"],
                ['name' => "Lagos"],
                ['name' => "Nasarawa"],
                ['name' => "Niger"],
                ['name' => "Ogun"],
                ['name' => "Ondo"],
                ['name' => "Osun"],
                ['name' => "Oyo"],
                ['name' => "Plateau"],
                ['name' => "Rivers"],
                ['name' => "Sokoto"],
                ['name' => "Taraba"],
                ['name' => "Yobe"],
                ['name' => "Zamfara"]
            ]);
        }
    }
}
