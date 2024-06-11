<x-filament-panels::page>
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

            <x-filament::button wire:click="manageSubscription">
                Manage Subscription
            </x-filament::button>

            @if (!$school->currentSubscription())
                <x-filament::button wire:click="subscribe">
                    Subscribe
                </x-filament::button>
            @endif
        </div>
    </div>

    <!-- Subscription data table -->
    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
