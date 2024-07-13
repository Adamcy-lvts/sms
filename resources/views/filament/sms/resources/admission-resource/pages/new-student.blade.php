<x-filament-panels::page>
    <div >
        {{ $this->form }}
    </div>
    <div class="flex justify-end">
        <x-filament::button size="sm" wire:click="create" color="primary">Add New Student</x-filament::button>
    </div>
</x-filament-panels::page>
