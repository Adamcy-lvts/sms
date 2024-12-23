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

Artisan::command('academic:update-period', function () {
    $this->updateCurrentSession();
    $this->updateCurrentTerm();

    $this->info('Current academic period updated successfully.');
    $this->info('Changes will be reflected in the next request.');
})->describe('Update the current academic session and term');

function updateCurrentSession()
{
    $now = now();
    $currentSession = AcademicSession::where('start_date', '<=', $now)
        ->where('end_date', '>=', $now)
        ->first();

    if ($currentSession) {
        AcademicSession::where('is_current', true)->update(['is_current' => false]);
        $currentSession->update(['is_current' => true]);
    }
}

function updateCurrentTerm()
{
    $now = now();
    $currentSession = AcademicSession::where('is_current', true)->first();

    if ($currentSession) {
        $currentTerm = $currentSession->terms()
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        if ($currentTerm) {
            Term::where('is_current', true)->update(['is_current' => false]);
            $currentTerm->update(['is_current' => true]);
        }
    }
}


Schedule::call(function () {
    $schools = School::with('subscriptions')->get();
    foreach ($schools as $school) {
        $subscription = $school->subscriptions()->latest()->first();
        if ($subscription && $subscription->is_on_trial === true && now()->greaterThanOrEqualTo($subscription->end_date)) {
            $subscription->update(['status' => 'expired']);
        }
    }
})->daily();

// Schedule the academic period update
Schedule::command('academic:update-period')->daily();


 // Run subscription check every hour
Schedule::command('subscriptions:check-expired')->hourly();
        
Schedule::command('backup:run --only-db')->dailyAt('06:00');  // Database backup every day at 6 AM
Schedule::command('backup:run')->monthlyOn(1, '00:00');       // Full app backup on the first day of every month at midnight
Schedule::command('backup:clean')->weeklyOn(1, '00:00');      // Cleanup old backups every Monday at midnight
Schedule::command('backup:monitor')->dailyAt('07:00');        // Checks backup health daily