<div>
    <x-filament::modal id="feedback-modal" wire:model="showModal" :close-by-clicking-away="false" width="md" alignment="center"
        icon="heroicon-o-chat-bubble-left-right">
        <x-slot name="heading">
            {{ $feedback?->title ?? 'Share Your Feedback' }}
        </x-slot>

        <x-slot name="description">
            {{ $feedback?->description ?? 'Help us improve your experience' }}
        </x-slot>

        {{-- Rest of your existing modal content --}}
    </x-filament::modal>
</div>
