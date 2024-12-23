 {{-- resources/views/filament/sms/pages/student-evaluations.blade.php --}}

<x-filament-panels::page>
    {{-- @if($this->termSummary)
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white mb-4">
                    Academic Summary
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Score</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($this->termSummary['total_score'], 1) }}
                        </dd>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Score</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($this->termSummary['average'], 1) }}
                        </dd>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Position</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $this->termSummary['position'] }} of {{ $this->termSummary['class_size'] }}
                        </dd>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Class Average</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($this->termSummary['class_stats']['class_average'], 1) }}
                        </dd>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Highest in Class</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($this->termSummary['class_stats']['highest_average'], 1) }}
                        </dd>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Lowest in Class</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($this->termSummary['class_stats']['lowest_average'], 1) }}
                        </dd>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Number of Subjects</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                            {{ $this->termSummary['total_subjects'] }}
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    @endif --}}

    {{ $this->form }}
</x-filament-panels::page>
