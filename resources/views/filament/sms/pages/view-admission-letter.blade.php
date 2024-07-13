<x-filament-panels::page>
    <div class="flex gap-4">
        <x-filament::button wire:click="downloadAdmissionLetter" icon-position="after" size="sm" outlined icon="heroicon-m-arrow-down-tray">
            Download 
        </x-filament::button>
        <x-filament::button href="{{ route('download.admission-letter.pdf', ['admission' => $admission]) }}"
            tag="a"  icon-position="after" size="sm" outlined icon="heroicon-m-arrow-down-tray">
            Download 
        </x-filament::button>
        <!-- Modified Print Button with Alpine.js -->
        <x-filament::button x-data @click="window.print()" icon-position="after" size="sm" outlined icon="heroicon-m-printer">
            Print 
        </x-filament::button>
        <x-filament::button     href="{{ route('admission-letter.show', ['admission' => $admission]) }}"
        tag="a">
            View PDF
        </x-filament::button>
    </div>

    <div class="p-8 bg-white shadow">
        @if(!empty($content))
            {!! $content !!}
        @else
            <p>No content available.</p>
        @endif
    </div>
    <div class="mt-8"></div>
</x-filament-panels::page>

