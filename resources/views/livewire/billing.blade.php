<x-filament-panels::page>

    <div>
        {{ $this->table }}
    </div>
    <x-filament::button wire:click="manageSubscription">
        Manage Subscription
    </x-filament::button>
</x-filament-panels::page>
