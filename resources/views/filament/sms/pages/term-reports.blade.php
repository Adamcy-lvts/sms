{{-- resources/views/filament/pages/term-report.blade.php --}}
<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if (filled($this->data['student_id'] ?? null))
            <x-filament::button wire:click="generateReport" type="button" size="lg" color="primary">
                <span wire:loading.remove wire:target="generateReport">
                    Generate Report
                </span>
                <span wire:loading wire:target="generateReport">
                    Generating...
                </span>
            </x-filament::button>
        @endif

        <livewire:report-progress />

        @if ($this->report)
            <div class="mt-4">
                @if (isset($this->report['error']))
                    <div class="p-4 bg-red-50 border-l-4 border-red-400 rounded-lg">
                        <p class="text-red-700">{{ $this->report['error'] }}</p>
                    </div>
                @elseif (empty($this->report['subjects']))
                    <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-yellow-400 mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <h3 class="text-lg font-medium text-yellow-800">No Academic Records Found</h3>
                                <p class="mt-2 text-sm text-yellow-700">
                                    There are no academic records available for this student in the selected term and
                                    session.
                                    Please verify that:
                                </p>
                                <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                                    <li>The student was enrolled during this period</li>
                                    <li>Grades have been entered for this term</li>
                                    <li>The selected academic session and term are correct</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 relative">
                        <!-- Centered watermark -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none">
                            <img src="{{ $logoData }}" alt="" class="w-[40%] max-w-2xl">
                            {{-- {{dd($logoData)}} --}}
                        </div>
                        
                        <!-- Content with relative positioning to appear above watermark -->
                        {{-- <div class="relative z-10"> --}}
                            @include('report-cards.sections.header', [
                                'headerConfig' => $this->report['template']->getHeaderConfig(),
                                'template' => $this->report['template'],
                                'data' => [
                                    'academic_info' => $this->report['academic_info'],
                                ],
                                'school' => $this->school,
                            ])

                            @include('report-cards.sections.student-info', [
                                'template' => $this->report['template'],
                                'data' => [
                                    'basic_info' => $this->report['basic_info'],
                                    'term_summary' => $this->report['term_summary'],
                                ],
                            ])

                            @include('report-cards.sections.grade-table', [
                                'template' => $this->report['template'],
                                'data' => [
                                    'subjects' => $this->report['subjects'],
                                    'summary' => $this->report['term_summary'],
                                ],
                            ])

                            @include('report-cards.sections.activities', [
                                'template' => $this->report['template'],
                                'data' => [
                                    'activities' => $this->report['activities'] ?? [],
                                ],
                                'config' => $this->report['template']->getActivitiesConfig(),
                            ])

                            @if (!empty($this->report['comments']))
                                @include('report-cards.sections.comments', [
                                    'template' => $this->report['template'],
                                    'data' => [
                                        'comments' => $this->report['comments'],
                                    ],
                                    'config' => $this->report['template']->getCommentsConfig(),
                                ])
                            @endif
                        {{-- </div> --}}
                    </div>

                    <div class="p-4 mt-4 bg-gray-50 rounded-lg text-sm text-gray-600">
                        <p>Generated on {{ $this->report['generated_at']->format('F j, Y \a\t g:i A') }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
