<?php

use App\Models\Bank;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('logs:clear', function () {
    file_put_contents(storage_path('logs/laravel.log'), '');
    $this->comment('Logs have been cleared!');
})->describe('Clear log files');



Artisan::command('fetch:banks', function () {
    $this->info('Fetching banks from Paystack...');

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
        $this->info('Banks list fetched and saved successfully.');
    } else {
        Log::error('Failed to fetch banks list: ' . $response->body());
        $this->error('Failed to fetch banks list. Check the logs for more details.');
    }
})->describe('Fetch the list of banks from Paystack and save to the database');