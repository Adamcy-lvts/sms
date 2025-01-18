<?php

use App\Models\Bank;
use App\Models\Term;
use App\Models\School;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;



// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Artisan::command('logs:clear', function () {
    file_put_contents(storage_path('logs/laravel.log'), '');
    $this->comment('Logs have been cleared!');
})->describe('Clear log files');

// Artisan::command('fetch:banks', function () {
//     $this->info('Fetching banks from Paystack...');

//     $response = Http::withToken(config('services.paystack.secret'))
//     ->get('https://api.paystack.co/bank');

//     if ($response->successful()) {
//         $banks = $response->json()['data'];
//         foreach ($banks as $bank) {
//             Bank::updateOrCreate(
//                 ['code' => $bank['code']],
//                 ['name' => $bank['name']]
//             );
//         }
//         $this->info('Banks list fetched and saved successfully.');
//     } else {
//         Log::error('Failed to fetch banks list: ' . $response->body());
//         $this->error('Failed to fetch banks list. Check the logs for more details.');
//     }
// })->describe('Fetch the list of banks from Paystack and save to the database');




// Schedule the academic period update
Schedule::command('academic:update-period')->daily();


// Run subscription check every hour
Schedule::command('subscriptions:check-expired')->hourly();
// Run once daily at a specific time (e.g., 8 AM)
Schedule::command('subscriptions:check-trials')->dailyAt('08:00')->appendOutputTo(storage_path('logs/trial-notifications.log'));

Schedule::command('backup:run --only-db')->dailyAt('06:00');  // Database backup every day at 6 AM
Schedule::command('backup:run')->monthlyOn(1, '00:00');       // Full app backup on the first day of every month at midnight
Schedule::command('backup:clean')->weeklyOn(1, '00:00');      // Cleanup old backups every Monday at midnight
Schedule::command('backup:monitor')->dailyAt('07:00');        // Checks backup health daily


