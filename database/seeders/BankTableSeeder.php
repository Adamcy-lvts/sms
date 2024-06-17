<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BankTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Bank::count() === 0) {
            $response = Http::withToken(config('services.paystack.secret'))
                ->get('https://api.paystack.co/bank');

            if ($response->successful()) {
                $banks = $response->json()['data'];
                foreach ($banks as $bank) {
                    Bank::updateOrCreate(
                        ['code' => $bank['code']],
                        ['name' => $bank['name']]
                    );
                }
            } else {
                Log::error('Failed to fetch banks list: ' . $response->body());
            }
        }
    }
}
