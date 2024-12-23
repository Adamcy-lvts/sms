{{-- @php
    $latestSubscription = '';
    $latestSubscription = $school->subscriptions()->latest()->first();
    $status = $latestSubscription->status ?? 'none';
    $badgeText = $status === 'active' ? 'Active' : ($status === 'cancelled' ? 'Cancelled' : 'No Subscription');
    $badgeClasses =
        $status === 'active'
            ? 'bg-green-50 text-green-700 ring-green-600/20 dark:border-green-600 dark:bg-green-700 dark:bg-opacity-25 dark:text-green-400'
            : ($status === 'cancelled'
                ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:border-yellow-600 dark:bg-yellow-700 dark:bg-opacity-25 dark:text-yellow-400'
                : 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:border-gray-600 dark:bg-gray-700 dark:bg-opacity-25 dark:text-gray-400');
@endphp

<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Subscription Plan:</span>
                <!-- Replace with dynamic content -->
                <span class="text-md font-semibold text-gray-800 dark:text-white">
                    {{ optional(optional($latestSubscription)->plan)->name ?? 'N/A' }}</span>
            </div>
            <div class="flex items-center">

                <span
                    class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-medium {{ $badgeClasses }} ring-1 ring-inset">
                    @if ($status === 'active')
                        <!-- Directly insert SVG for active status -->
                        <svg height="20px" version="1.1" viewBox="0 0 20 20" width="20px"
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-2 fill-current text-green-500 dark:text-green-400">
                            <g fill-rule="evenodd">
                                <g>
                                    <path
                                        d="M5.9,8.1 L4.5,9.5 L9,14 L19,4 L17.6,2.6 L9,11.2 L5.9,8.1 L5.9,8.1 Z M18,10 C18,14.4 14.4,18 10,18 C5.6,18 2,14.4 2,10 C2,5.6 5.6,2 10,2 C10.8,2 11.5,2.1 12.2,2.3 L13.8,0.7 C12.6,0.3 11.3,0 10,0 C4.5,0 0,4.5 0,10 C0,15.5 4.5,20 10,20 C15.5,20 20,15.5 20,10 L18,10 L18,10 Z" />
                                </g>
                            </g>
                        </svg>
                    @endif
                    {{ $badgeText }}

                </span>

            </div>

        </div>

    </x-filament::section>
</x-filament-widgets::widget> --}}
@php
    use Filament\Facades\Filament;

    $school = Filament::getTenant();
    $currentSubscription = $school->subscriptions()->latest()->first();
    // dd($currentSubscription);
    // dd($currentSubscription);
    $status = $currentSubscription?->status ?? 'none';

    $statusConfig = [
        'active' => [
            'text' => 'Active',
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
        <div class="flex flex-col gap-0.3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-5">Subscription Plan:</span>
                    <span class="text-sm font-semibold text-gray-800 dark:text-white">
                        {{ $currentSubscription?->plan?->name ?? 'N/A' }}
                    </span>
                </div>
                <span
                    class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-medium {{ $currentStatus['classes'] }} ring-1 ring-inset">
                    {{ $currentStatus['text'] }}
                </span>
            </div>

            <span class="text-xs text-gray-500 dark:text-gray-400">
                @if ($currentSubscription?->ends_at)
                    Expires on {{ Carbon\Carbon::parse($currentSubscription->ends_at)->format('F j, Y') }}
                @endif
            </span>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
