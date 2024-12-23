<div>
    @if ($batchId)
        <x-filament::section>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium">Report Generation Progress</h3>
                    @if ($status === 'processing')
                        <button wire:click="cancelGeneration" class="text-sm text-red-600 hover:text-red-800">
                            Cancel Generation
                        </button>
                    @endif
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>{{ $processedJobs }} of {{ $totalJobs }} students reports generated</span>
                        <span>{{ $progress }}%</span>
                    </div>

                    <div x-data="{
                        progress: 0,
                        progressInterval: null,
                    }" x-init="progressInterval = setInterval(() => {
                        progress = {{ $progress }};
                        if ({{ $progress }} >= 100) {
                            clearInterval(progressInterval);
                        }
                    }, 100);"
                        class="relative w-full h-3 overflow-hidden rounded-full bg-gray-100">
                        <span :style="'width:' + {{ $progress }} + '%'"
                            class="absolute w-24 h-full duration-300 ease-linear bg-primary-600" x-cloak></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm"
                            :class="{
                                'text-emerald-600': @js($this->progressColor === 'success'),
                                'text-amber-600': @js($this->progressColor === 'warning'),
                                'text-red-600': @js($this->progressColor === 'danger'),
                                'text-primary-600': @js($this->progressColor === 'primary')
                            }">
                            {{ $this->statusText }}
                        </p>

                        @if ($failedJobs > 0)
                            <span class="text-sm text-red-600">
                                {{ $failedJobs }} failed
                            </span>
                        @endif
                    </div>
                </div>

                @if ($downloadUrl)
                    <div class="flex justify-end mt-4">
                        <a href="{{ $downloadUrl }}" target="_blank"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-colors duration-200 bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Reports Cards
                        </a>
                    </div>
                @endif
            </div>

            {{-- Auto-refresh while processing --}}
            @if ($status === 'processing')
                <div wire:poll.5s="checkProgress"></div>
            @endif

        </x-filament::section>
    @endif

</div>
