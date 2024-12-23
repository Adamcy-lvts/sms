@php
    $session = config('app.current_session');
    $term = config('app.current_term');
@endphp

<div class="hidden md:flex items-center gap-3 px-3 py-2 text-sm">
    @if ($session || $term)
        @if ($session)
            <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Session:</span>
                <span class="px-2 py-0.5 rounded-md bg-primary-500/10 text-primary-600 dark:text-primary-400">
                    {{ $session->name }}
                </span>
            </div>
        @endif

        @if ($term)
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Term:</span>
                <span class="px-2 py-0.5 rounded-md bg-primary-500/10 text-primary-600 dark:text-primary-400">
                    {{ $term->name }}
                </span>
            </div>
        @endif
    @else
        <span class="text-gray-500"></span>
    @endif
</div>