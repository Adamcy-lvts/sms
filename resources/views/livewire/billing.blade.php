<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Subscription Management Section -->
        <x-filament::section>
            <div class="space-y-4">
                @if ($school->currentSubscription()->next_payment_date ?? null)
                    <div class="text-sm text-warning-500">
                        Next Payment Date: <strong class="font-medium">{{ $nextBillingDate }}</strong>
                    </div>
                @endif

                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if ($school->currentSubscription())
                        You are currently subscribed. To manage your subscription settings or to download invoices, click 'Manage Subscription' below.
                    @else
                        You're not currently subscribed to any plan. Click 'Subscribe' below to choose a plan that fits your needs.
                    @endif
                </p>

                <div class="flex gap-4">
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
        </x-filament::section>

        <!-- Cancel Subscription Section -->
        @if ($school->currentSubscription())
            <x-filament::section>
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        If you wish to cancel your subscription, you can do so at any time.
                        Please note that canceling the subscription will revoke access to premium features after the end of your current billing period.
                    </p>
                    
                    <x-filament::button 
                        size="sm" 
                        color="danger"
                        wire:click="cancelSubs">
                        Cancel Subscription
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        <!-- Subscription History Section -->
        <x-filament::section>
            <x-slot name="heading">
                Subscription History
            </x-slot>

            {{ $this->table }}
        </x-filament::section>

        <!-- Cancel Subscription Modal -->
        <x-filament::modal id="cancel-subscription-modal" alignment="center" icon="heroicon-o-exclamation-triangle"
            icon-color="danger" width="xl">
            <x-slot name="heading">
                {{ 'Cancel Subscription' }}
            </x-slot>

            <x-slot name="description">
                Are you sure you want to cancel your subscription? This action cannot be undone.
            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-between">
                    <x-filament::button size="xs" color="gray"
                        x-on:click="$dispatch('close-modal', { id: 'cancel-subscription-modal'})">
                        Close
                    </x-filament::button>

                    <x-filament::button color="danger" wire:click="cancelSubscription">
                        Cancel Subscription
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::modal>
    </div>
</x-filament-panels::page>
