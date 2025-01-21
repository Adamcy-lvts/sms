{{-- resources/views/filament/sms/pages/auth/edit-profile.blade.php --}}
<x-dynamic-component :component="static::isSimple() ? 'filament-panels::page.simple' : 'filament-panels::page'">
    <style>
        .fi-simple-main {
            max-width: 50% !important;
            !@apply bg-gray-50 dark:bg-gray-950;
            /* Adjust width to 90% of the viewport by default */
        }

        @media (max-width: 768px) {

            /* For tablets and below */
            .fi-simple-main {
                max-width: 95% !important;
            }
        }

        @media (max-width: 480px) {

            /* For mobile devices */
            .fi-simple-main {
                max-width: 95% !important;
            }
        }
    </style>
    <div class="grid auto-cols-fr gap-y-8">
        {{-- Profile Header --}}
        <div class="fi-simple-layout flex flex-col items-center justify-center">
            <div class="space-y-2 text-center">
                <div class="mb-4">
                    <img src="{{ auth()->user()->getFilamentAvatarUrl() }}" alt="{{ auth()->user()->getFilamentName() }}"
                        class="w-32 h-32 rounded-full mx-auto" />
                </div>

                <h2 class="text-2xl font-bold">
                    {{ auth()->user()->getFilamentName() }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1  gap-8">
            {{-- Main Form Section --}}
            <div
                class=" bg-white dark:bg-gray-900  shadow sm:rounded-xl sm:ring-1 sm:ring-gray-950/5 dark:sm:ring-white/10">
                <x-filament-panels::form wire:submit="save">
                    {{ $this->form }}

                    <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
                </x-filament-panels::form>
            </div>

            {{-- Account Status & Quick Actions --}}
           
           
        </div>
    </div>
</x-dynamic-component>
