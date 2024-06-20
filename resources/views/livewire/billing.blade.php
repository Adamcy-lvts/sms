<x-filament-panels::page>
    <div class="bg-gray-800 dark:bg-gray-800 text-gray-600 dark:text-white p-4 rounded-lg shadow space-y-4 ">

        <!-- Subscription Status and Details -->

        @if ($school->currentSubscription()->next_payment_date ?? null)
            <div class="text-sm text-yellow-300">
                Next Payment Date: <strong class="font-medium">{{ $nextBillingDate }}</strong>
            </div>
        @endif
        @if ($school->currentSubscription())
            <p class="text-gray-600 dark:text-white text-sm">You are currently subscribed. To manage your subscription settings or to download invoices, click 'Manage
                Subscription' below.</p>
        @else
            <p class="text-gray-600 dark:text-white text-sm">You're no longer subscribed to SMS. To re-subscribe, click 'Subscribe' below. To update your payment card
                click 'Manage Subscription' below.</p>
        @endif
        <div class="flex gap-4">
            <!-- Manage Subscription Section -->
            <div>
                <x-filament::button
                    class="px-4 py-2 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition duration-300 ease-in-out"
                    wire:click="manageSubscription">
                    Manage Subscription
                </x-filament::button>
            </div>
            <!-- Subscribe Section -->
            @if (!$school->currentSubscription())
                <div>
                    <x-filament::button
                        class="px-4 py-2 bg-green-500 text-white text-sm rounded hover:bg-green-600 transition duration-300 ease-in-out"
                        wire:click="subscribe">
                        Subscribe
                    </x-filament::button>
                </div>
            @endif


        </div>
    </div>

    <!-- Action Buttons -->
    <!-- Buttons -->
    <!-- Subscription Status Information -->
    <div class="flex items-center text-sm space-x-2">
        <i class="fas fa-info-circle"></i>

    </div>



    <!-- Cancel Subscription Section -->
    @if ($school->currentSubscription())
        <div class="bg-gray-800 dark:bg-gray-800 text-white p-4 rounded-lg shadow space-y-4 ">

            <div class="text-red-500">

                <p class="text-sm text-gray-600 dark:text-white">
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
