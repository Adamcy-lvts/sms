<x-filament-panels::page>
    {{-- Alert for draft management --}}
    @if ($hasDraft)
        <div class="mb-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p>You have an unsaved draft from {{ $lastAutoSave }}.</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::button color="gray" wire:click="restoreDraft">
                        Restore Draft
                    </x-filament::button>
                    <x-filament::button color="danger" wire:click="clearDraft">
                        Clear Draft
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif


    {{-- Main form --}}
    <form wire:submit="saveGrades" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-x-3 pt-6 border-t">
            <x-filament::button type="submit" color="success" icon="heroicon-o-check">
                Save Grades
            </x-filament::button>
        </div>
    </form>

    {{-- Custom Scripts --}}
    @script
        <script>
            // livewire.on auto-save-enabled dispatchd save-draft event to save draft every 1 minute
            Livewire.on('auto-save-enabled', () => {
                setInterval(() => {
                    $wire.dispatch('save-draft');
                    console.log('Draft saved');
                }, 60000);
            });
        </script>
    @endscript
</x-filament-panels::page>
