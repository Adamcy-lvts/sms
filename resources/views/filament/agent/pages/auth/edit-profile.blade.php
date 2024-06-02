<x-filament-panels::page>
    {{ $this->form }}
    <div class="flex justify-end">
        <x-filament::button class="w-40 " wire:click="save" size="sm">
            Save
        </x-filament::button>
    </div>

</x-filament-panels::page>
