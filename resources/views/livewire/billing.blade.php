<x-filament-panels::page>
    <div class="bg-gray-800 dark:bg-gray-800 text-white p-4 rounded-lg shadow space-y-4 ">

        <!-- Subscription Status and Details -->
        <div class="space-y-2">

            @if ($school->currentSubscription())
                <div class="text-green-400 text-sm">
                    Current Plan: <strong class="font-medium">{{ $planName }}</strong>
                    <span
                        class="inline-block bg-green-500 text-white text-xs font-semibold px-2 rounded-full ml-2">Active</span>
                </div>
        </div>
        <div class="text-sm text-yellow-300">
            Next Payment Date: <strong class="font-medium">{{ $nextBillingDate }}</strong>
        </div>
        @endif
    </div>

    <!-- Action Buttons -->
    <!-- Buttons -->
    <!-- Subscription Status Information -->
    <div class="flex items-center text-sm space-x-2 mb-4">
        <i class="fas fa-info-circle"></i>
        @if ($school->currentSubscription())
            <p>You are currently subscribed. To manage your subscription settings or to download invoices, click 'Manage
                Subscription' below.</p>
        @else
            <p>You're no longer subscribed to SMS. To re-subscribe, click 'Subscribe' below. To update your payment card
                click 'Manage Subscription' below.</p>

        @endif
    </div>
    <div class="flex gap-4">

        <!-- Manage Subscription Section -->
        <div>
            <x-filament::button size="sm" wire:click="manageSubscription">
                Manage Subscription
            </x-filament::button>
        </div>

        <!-- Subscribe Section -->
        @if (!$school->currentSubscription())
            <div>

                <x-filament::button size="sm" wire:click="subscribe">
                    Subscribe
                </x-filament::button>
            </div>
        @endif

    </div>


    </div>
    <!-- Cancel Subscription Section -->
    @if ($school->currentSubscription())
        <div class="bg-gray-800 dark:bg-gray-800 text-white p-4 rounded-lg shadow space-y-4 ">

            <div class="text-red-500">

                <p class="text-sm">
                    If you wish to cancel your subscription, you can do so at any time.
                    Please note that canceling the subscription will revoke access to premium features after the end of
                    your current billing period.
                </p>
                <x-filament::button size="sm" class="bg-red-600 hover:bg-red-700 mt-2"
                    wire:click="cancelSubscription">
                    Cancel Subscription
                </x-filament::button>
            </div>
        </div>
    @endif

    <!-- Placeholder for subscription history or additional information -->
    <div class="mt-4">
        <h3 class="font-semibold text-lg text-gray-700 dark:text-gray-300 mb-2">
            Subscription History
        </h3>
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
