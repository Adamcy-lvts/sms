@php
    use Filament\Facades\Filament;

    $school = Filament::getTenant();
    $currentSubscription = $school->subscriptions()->latest()->first();
    $status = $currentSubscription?->status ?? 'none';
    $isTrial = $currentSubscription?->is_on_trial ?? false;

    $statusConfig = [
        'active' => [
            'text' => $isTrial ? 'Trial Active' : 'Active',
            'classes' =>
                'bg-green-50 text-green-700 ring-green-600/20 dark:border-green-600 dark:bg-green-700 dark:bg-opacity-25 dark:text-green-400',
        ],
        'cancelled' => [
            'text' => 'Cancelled',
            'classes' =>
                'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:border-yellow-600 dark:bg-yellow-700 dark:bg-opacity-25 dark:text-yellow-400',
        ],
        'expired' => [
            'text' => 'Expired',
            'classes' =>
                'bg-red-50 text-red-700 ring-red-600/20 dark:border-red-600 dark:bg-red-700 dark:bg-opacity-25 dark:text-red-400',
        ],
        'none' => [
            'text' => 'No Subscription',
            'classes' =>
                'bg-gray-50 text-gray-700 ring-gray-600/20 dark:border-gray-600 dark:bg-gray-700 dark:bg-opacity-25 dark:text-gray-400',
        ],
    ];

    $currentStatus = $statusConfig[$status] ?? $statusConfig['none'];
@endphp

<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="flex flex-col gap-0.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-5">Subscription Plan:</span>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white">
                        {{ $currentSubscription?->plan?->name ?? 'N/A' }}
                    </span>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span
                        class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-medium {{ $currentStatus['classes'] }} ring-1 ring-inset">
                        {{ $currentStatus['text'] }}
                    </span>
                    {{-- @if ($isTrial)
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                            Trial ends in {{ now()->diffInDays($currentSubscription->trial_ends_at) }} days
                        </span>
                    @endif --}}
                </div>
            </div>

            <span class="text-xs text-gray-500 dark:text-gray-400">
                @if ($currentSubscription?->ends_at)
                    {{ $isTrial ? 'Trial expires' : 'Expires' }} on {{ Carbon\Carbon::parse($currentSubscription->ends_at)->format('F j, Y') }}
                @endif
            </span>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
