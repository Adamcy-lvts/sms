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

    $isAdmin = $this->isAdmin;
    $user = auth()->user();
    $lastLogin = $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never';

    $roleConfig = match ($user->roles->first()?->name) {
        'teacher' => [
            'text' => 'Teacher',
            'classes' =>
                'bg-blue-50 text-blue-700 ring-blue-600/20 dark:border-blue-600 dark:bg-blue-700 dark:bg-opacity-25 dark:text-blue-400',
        ],
        'principal' => [
            'text' => 'Principal',
            'classes' =>
                'bg-green-50 text-green-700 ring-green-600/20 dark:border-green-600 dark:bg-green-700 dark:bg-opacity-25 dark:text-green-400',
        ],
        'vice_principal' => [
            'text' => 'Vice Principal',
            'classes' =>
                'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:border-yellow-600 dark:bg-yellow-700 dark:bg-opacity-25 dark:text-yellow-400',
        ],
        'admin' => [
            'text' => 'Admin',
            'classes' =>
                'bg-red-50 text-red-700 ring-red-600/20 dark:border-red-600 dark:bg-red-700 dark:bg-opacity-25 dark:text-red-400',
        ],
        'super_admin' => [
            'text' => 'Super Admin',
            'classes' =>
                'bg-purple-50 text-purple-700 ring-purple-600/20 dark:border-purple-600 dark:bg-purple-700 dark:bg-opacity-25 dark:text-purple-400',
        ],
        'accountant' => [
            'text' => 'Accountant',
            'classes' =>
                'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:border-indigo-600 dark:bg-indigo-700 dark:bg-opacity-25 dark:text-indigo-400',
        ],
        default => [
            'text' => 'User',
            'classes' =>
                'bg-gray-50 text-gray-700 ring-gray-600/20 dark:border-gray-600 dark:bg-gray-700 dark:bg-opacity-25 dark:text-gray-400',
        ],
    };
@endphp

<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section class="bg-white dark:bg-gray-800 rounded-lg shadow">
        @if ($isAdmin)
            {{-- Show subscription details for admins --}}
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-5">Subscription
                            Plan:</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-white">
                            {{ $currentSubscription?->plan?->name ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span
                            class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-medium {{ $currentStatus['classes'] }} ring-1 ring-inset">
                            {{ $currentStatus['text'] }}
                        </span>

                    </div>
                </div>

                <span class="text-xs text-gray-500 dark:text-gray-400">
                    @if ($currentSubscription?->ends_at)
                        {{ $isTrial ? 'Trial expires' : 'Expires' }} on
                        {{ Carbon\Carbon::parse($currentSubscription->ends_at)->format('F j, Y') }}
                    @endif
                </span>
            </div>
        @else
            <div class="flex flex-col">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-5">Role</span>
                    <span
                        class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-medium ring-1 ring-inset {{ $roleConfig['classes'] }}">
                        {{ $roleConfig['text'] }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Last login: {{ $lastLogin }}
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
