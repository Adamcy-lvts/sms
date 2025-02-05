<div class="space-y-4 p-2">
    <!-- Header -->
    <div class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3">

        <div class="flex-1">Student Name</div>
        <div class="w-40">Class</div>
        <div class="w-40 text-right">Session Fee</div>
        <div class="w-40 text-right">Term Fee</div>
    </div>

    <!-- List Container -->
    <div class="space-y-1">
        @foreach($students as $student)
            <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $student['student']->full_name }}
                </div>
                <div class="w-40 text-sm text-gray-500 dark:text-gray-400">
                    {{ $student['student']->classRoom?->name }}
                </div>
                <div class="w-40 text-right text-sm text-gray-500 dark:text-gray-400">
                    ₦{{ number_format($student['amount_session'], 2) }}
                </div>
                <div class="w-40 text-right text-sm text-gray-500 dark:text-gray-400">
                    ₦{{ number_format($student['amount_term'], 2) }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Summary Footer -->
    <div class="flex items-center justify-end border-t dark:border-gray-700 pt-3 mt-3 px-3">
        <div class="flex items-center space-x-4">
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                Total:
            </div>
            <div class="w-40 text-right">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Session: ₦{{ number_format($totalSessionAmount, 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Term: ₦{{ number_format($totalTermAmount, 2) }}
                </div>
            </div>
        </div>
    </div>
</div>