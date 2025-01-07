<x-filament-panels::page>
    <!-- Actions Bar -->
    <div class="w-full max-w-5xl mx-auto">
        <div class="max-w-5xl mx-auto mb-4 flex justify-end">
            @if ($renderedContent)
                <x-filament::button wire:click="downloadPdf" icon="heroicon-o-arrow-down-tray">
                    Download PDF
                </x-filament::button>
            @endif
        </div>
        <!-- Main container with min-height -->
        <div class="bg-white p-8 rounded-lg shadow-sm  min-h-[calc(100vh-8rem)] relative">
            <!-- Centered watermark absolutely positioned within container -->
            <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none">
                <img src="{{ $logoData }}" alt="" class="w-[40%] max-w-2xl">
            </div>

            <!-- Content wrapper with z-index -->
            <div class="relative z-10">
                <header class="mb-8 text-center">
                    <!-- Logo -->
                    @if ($school->logo)
                        <div class="mb-1.5">
                            <img src="{{ $logoData }}" alt="{{ $school->name }}"
                                class="h-20 w-auto object-contain mx-auto">
                        </div>
                    @endif

                    <!-- School info with tighter spacing -->
                    <div class="space-y-0">
                        @if ($school->name_ar)
                            <h2 class="text-md font-semibold text-gray-700"
                                style="font-family: 'Noto Naskh Arabic', serif;">
                                {{ $school->name_ar }}
                            </h2>
                        @endif

                        <h1 class="text-md font-bold text-gray-800">
                            {{ strtoupper($school->name) }}
                        </h1>

                        <div class="mt-1 space-y-0.5 text-gray-600">
                            <p class="text-xs">{{ $school->address }}</p>
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <span>Tel: {{ $school->phone }}</span>
                                <span class="text-gray-400">|</span>
                                <span>{{ $school->email }}</span>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Letter content -->
                <div class="prose max-w-none mb-8">
                    @if ($renderedContent)
                        {!! tiptap_converter()->asHTML($renderedContent) !!}
                    @else
                        <div class="text-center text-gray-500">
                            No active admission letter template found.
                        </div>
                    @endif
                </div>


            </div>

            <!-- Footer -->
            <footer class="absolute bottom-0 left-0 right-0 border-t border-gray-200 p-4">
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span>{{ $school->name }}</span>
                    <span>Admission Letter View</span>
                </div>
            </footer>
        </div>
    </div>
</x-filament-panels::page>
