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
        $this->info('Checking for expired subscriptions...');

        try {
            // Check regular subscriptions that have ended
            $expiredSubscriptions = Subscription::query()
                ->where('status', 'active')
                ->where('ends_at', '<=', now())
                ->update(['status' => 'expired']);

            $this->info("Updated {$expiredSubscriptions} expired regular subscriptions");

            // Check trial subscriptions that have ended
            $expiredTrials = Subscription::query()
                ->where('status', 'active')
                ->where('is_on_trial', true)
                ->where('trial_ends_at', '<=', now())
                ->update([
                    'status' => 'expired',
                    'is_on_trial' => false
                ]);

            $this->info("Updated {$expiredTrials} expired trial subscriptions");

            // Log success
            Log::info('Subscription check completed', [
                'expired_subscriptions' => $expiredSubscriptions,
                'expired_trials' => $expiredTrials
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check expired subscriptions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error('Failed to check expired subscriptions. Check logs for details.');
            return 1;
        }
    }
}