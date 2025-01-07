{{-- <x-filament-panels::page.simple>

  
        <style>
            .fi-simple-main {
                max-width: 60% !important;
                !@apply bg-gray-50 dark:bg-gray-950;
                /* Adjust width to 90% of the viewport by default */
            }

            @media (max-width: 768px) {

                /* For tablets and below */
                .fi-simple-main {
                    max-width: 85% !important;
                }
            }

            @media (max-width: 480px) {

                /* For mobile devices */
                .fi-simple-main {
                    max-width: 95% !important;
                }
            }
        </style>

        @if (filament()->hasLogin())
            <x-slot name="subheading">
                {{ __('filament-panels::pages/auth/register.actions.login.before') }}

                {{ $this->loginAction }}
            </x-slot>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <x-filament-panels::form wire:submit="register">

            {{ $this->form }}

           
        </x-filament-panels::form>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}



</x-filament-panels::page.simple> --}}

<x-filament-panels::page.simple>
    <div class="flex flex-col items-center justify-center">
        <!-- Headings -->
        <div class="text-center mb-8">
           
            @if (filament()->hasLogin())
                <x-slot name="subheading">
                    {{ __('filament-panels::pages/auth/register.actions.login.before') }}

                    {{ $this->loginAction }}
                </x-slot>
            @endif
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                Create Your School Account
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Set up your school management system in minutes
            </p>


        </div>

        <!-- Form Content -->
        <div class="w-full">
            <style>
                .fi-simple-main {
                    max-width: 65% !important;
                    @apply bg-gray-50 dark:bg-gray-950;
                }

                /* Your media queries... */
            </style>

            @if (filament()->hasLogin())
                <x-slot name="subheading">
                    {{ __('filament-panels::pages/auth/register.actions.login.before') }}
                    {{ $this->loginAction }}
                </x-slot>
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

            <x-filament-panels::form wire:submit="register">
                {{ $this->form }}
            </x-filament-panels::form>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-gray-500 dark:text-gray-400">
            <p>
                By registering, you agree to our
                <a href="#" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">Terms of
                    Service</a>
                and
                <a href="#" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">Privacy
                    Policy</a>
            </p>
            <p class="mt-2">
                &copy; {{ date('Y') }} School SMS. All rights reserved.
            </p>
        </div>
    </div>
</x-filament-panels::page.simple>
