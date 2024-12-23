<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';
    protected $description = 'Check and update expired subscriptions';

    public function handle()
    {
        try {
            $this->info('Checking for expired subscriptions...');
            
            // Get active or trial subscriptions that have expired
            $expiredSubscriptions = Subscription::where(function($query) {
                $query->where('status', 'active')
                    ->orWhere('is_on_trial', true);
            })
            ->where(function($query) {
                $query->where('ends_at', '<', now())
                    ->orWhere('trial_ends_at', '<', now());
            })
            ->get();

            if ($expiredSubscriptions->isEmpty()) {
                $this->info('No expired subscriptions found.');
                return;
            }

            foreach ($expiredSubscriptions as $subscription) {
                $subscription->update([
                    'status' => 'expired',
                    'is_on_trial' => false
                ]);

                Log::info('Subscription marked as expired', [
                    'subscription_id' => $subscription->id,
                    'school_id' => $subscription->school_id,
                    'end_date' => $subscription->ends_at
                ]);
            }

            $this->info("{$expiredSubscriptions->count()} expired subscriptions updated.");
        } catch (\Exception $e) {
            Log::error('Error checking expired subscriptions:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Error checking expired subscriptions. Check logs for details.');
        }
    }
}