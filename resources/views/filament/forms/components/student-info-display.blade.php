@php
    $student = \App\Models\Student::find($getState()['student_id'] ?? null);
    $termSummary = $this->termSummary ?? null;
@endphp

@if ($student && $termSummary)
    <x-filament::section>
        {{-- Centered Student Profile --}}
        <div class="flex flex-col items-center mb-8">
            <x-filament::avatar size="xl" src="{{ $student->profile_picture_url ?? '' }}"
                alt="{{ $student->full_name }}" class="w-24 h-24 sm:w-32 sm:h-32" />
            <div class="mt-4 text-center">
                <div class="text-md sm:text-2xl font-bold dark:text-white">{{ $student->full_name }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $termSummary['basic_info']['class'] }} •
                    {{ $termSummary['academic_info']['term']['name'] }}
                </div>
            </div>
        </div>

        {{-- Stats Cards - Horizontal Layout --}}
        <div class="flex flex-col lg:flex-row lg:justify-between gap-4 lg:gap-6">
            {{-- Average Score --}}
            <div
                class="flex-1 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-md sm:text-3xl font-bold text-warning-500 dark:text-warning-400">
                    {{ number_format($termSummary['summary']['average'], 1) }}%
                </div>
                <div class="font-medium text-gray-600 dark:text-gray-300">Average Score</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    Grade {{ $termSummary['summary']['grade'] }} •
                    Total Score: {{ $termSummary['summary']['total_score'] }}
                </div>
            </div>

            {{-- Position --}}
            <div
                class="flex-1 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-md sm:text-3xl font-bold text-success-500 dark:text-success-400">
                    {{ $termSummary['summary']['position'] }}
                    <span class="text-md sm:text-xl">/{{ $termSummary['summary']['class_size'] }}</span>
                </div>
                <div class="font-medium text-gray-600 dark:text-gray-300">Class Position</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    {{ $termSummary['summary']['position'] }}{{ substr($termSummary['summary']['position'], -1) == 1 ? 'st' : (substr($termSummary['summary']['position'], -1) == 2 ? 'nd' : (substr($termSummary['summary']['position'], -1) == 3 ? 'rd' : 'th')) }}
                    of {{ $termSummary['summary']['class_size'] }} students
                </div>
            </div>

            {{-- Attendance --}}
            <div
                class="flex-1 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-md sm:text-3xl font-bold text-primary-500 dark:text-primary-400">
                    {{ $termSummary['attendance']['present'] }}
                    <span class="text-md sm:text-xl">/{{ $termSummary['attendance']['school_days'] }}</span>
                </div>
                <div class="font-medium text-gray-600 dark:text-gray-300">Days Present</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    {{ $termSummary['summary']['attendance_percentage'] }} Attendance Rate
                </div>
            </div>

            {{-- Subjects --}}
            <div
                class="flex-1 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-md sm:text-3xl font-bold text-info-500 dark:text-info-400">
                    {{ $termSummary['summary']['total_subjects'] }}
                </div>
                <div class="font-medium text-gray-600 dark:text-gray-300">Total Subjects</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    Scored: {{ $termSummary['summary']['total_score'] }} points
                </div>
            </div>
        </div>
    </x-filament::section>
@endif




