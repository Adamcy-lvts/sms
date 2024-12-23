<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form Section --}}
        {{ $this->form }}

        {{-- Students Table Section --}}
        @if ($this->data['class_room_id'] ?? null)
            <x-filament::section>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium">Student Attendance</h2>

                        {{-- Bulk Actions --}}
                        <div class="flex gap-2">
                            <x-filament::button wire:click="$set('allPresent', true)" icon="heroicon-m-check-circle"
                                color="success" size="sm">
                                Mark All Present
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Students Table --}}
                    {{ $this->table }}

                    {{-- Submit Button --}}
                    <div class="flex justify-end mt-4">
                        <x-filament::button wire:click="create" type="submit" icon="heroicon-m-check">
                            Record Attendance
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
