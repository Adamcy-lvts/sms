{{-- <x-filament-panels::page>
    <!-- Subscription status and control buttons -->
    <div class="bg-gray-600 text-white p-4 rounded-lg shadow space-y-2">
        <div class="text-lg font-semibold">
            Subscription Management
        </div>

        <!-- Subscription status message -->
        @if ($school->currentSubscription())
            <div class="text-green-300">
                You are currently subscribed.
            </div>
        @else
            <div class="text-red-300">
                You are not subscribed. Click 'Subscribe' to start your subscription.
            </div>
        @endif

        <!-- Buttons -->
        <div class="flex justify-between space-x-2">

            <x-filament::button size="sm" wire:click="manageSubscription">
                Manage Subscription
            </x-filament::button>

            @if (!$school->currentSubscription())
                <x-filament::button size="sm" wire:click="subscribe">
                    Subscribe
                </x-filament::button>
            @endif
        </div>
    </div>

    <!-- Subscription data table -->
    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page> --}}

<x-filament-panels::page>
    <div class="bg-gray-800 dark:bg-gray-800 text-white p-4 rounded-lg shadow space-y-4">
        {{-- <h2 class="text-xl font-semibold">
            Subscription Management
        </h2> --}}

        <!-- Subscription Status and Details -->
        <div class="space-y-2">
            <div class="text-green-400">
                Current Plan: <strong class="font-medium">{{ $planName }}</strong>
            </div>
            <div class="text-yellow-300">
                Next Billing Date: <strong class="font-medium">{{ $nextBillingDate }}</strong>
            </div>

        </div>

        <!-- Action Buttons -->
        <!-- Buttons -->
        <div class="flex justify-between space-x-2">

            <x-filament::button size="sm" wire:click="manageSubscription">
                Manage Subscription
            </x-filament::button>

            @if (!$school->currentSubscription())
                <x-filament::button size="sm" wire:click="subscribe">
                    Subscribe
                </x-filament::button>
            @endif

            @if ($school->canRenewSubscription())
                <x-filament::button wire:click="renewSubscription" class="bg-yellow-600 hover:bg-yellow-700">
                    Renew Subscription
                </x-filament::button>
            @endif
        </div>
    </div>

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
