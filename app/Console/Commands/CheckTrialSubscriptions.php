<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Subscription;
use Illuminate\Console\Command;
use App\Notifications\TrialExpirationNotification;

class CheckTrialSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-trial-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptions = Subscription::query()
            ->onTrial()
            ->whereIn('trial_ends_at', [
                Carbon::now()->addDays(3)->startOfDay(),
                Carbon::now()->addDay()->startOfDay()
            ])
            ->with('school') // Eager load school relationship
            ->get();

        foreach ($subscriptions as $subscription) {
            $daysRemaining = Carbon::now()->startOfDay()
                ->diffInDays($subscription->trial_ends_at->startOfDay());

            $superAdmin = $subscription->school->getSuperAdmin();

            if ($superAdmin) {
                $superAdmin->notify(new TrialExpirationNotification(
                    $subscription->school,
                    $daysRemaining
                ));

                $this->info("Sent {$daysRemaining}-day notification to {$subscription->school->name}");
            }
        }

        $this->info('Trial subscription check completed');
    }
}
