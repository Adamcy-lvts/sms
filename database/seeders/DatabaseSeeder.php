<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Template;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(StatusTableSeeder::class);
        $this->call(UserTableSeeder::class);
        $this->call(PlansTableSeeder::class);
        $this->call(BankTableSeeder::class);
        $this->call(StateTableSeeder::class);
        $this->call(LgaTableSeeder::class);
        $this->call(SchoolTableSeeder::class);
        $this->call(SessionAndTermSeeder::class);
        $this->call(ClassRoomTableSeeder::class);
        $this->call(SubjectTableSeeder::class);
        $this->call(AdmissionTableSeeder::class);
        $this->call(TemplateTableSeeder::class);
        $this->call(PaymentMethodTableSeeder::class);
        $this->call(PaymentTypeTableSeeder::class);
        $this->call(DesignationTableSeeder::class);

    }
}
