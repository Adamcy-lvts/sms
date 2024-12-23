{{-- resources/views/livewire/attendance-table.blade.php --}}
<div>
    <div class="mb-4">
        {{ $this->form }}
    </div>

    @if ($this->data['class_room_id'] ?? false)
        <div class="bg-white rounded-lg shadow">
            {{ $this->table }}
        </div>
    @else
        <div class="text-center p-8 bg-white rounded-lg shadow">
            <x-filament::icon alias="empty-state" icon="heroicon-o-users" class="mx-auto h-16 w-16 text-gray-400" />
            <h3 class="mt-4 text-lg font-medium text-gray-900">No Class Selected</h3>
            <p class="mt-2 text-sm text-gray-500">Please select a class to view and manage student attendance.</p>
        </div>
    @endif
</div>
