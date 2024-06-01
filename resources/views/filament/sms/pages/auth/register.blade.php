<x-filament-panels::page.simple>

  
        <style>
            .fi-simple-main {
                max-width: 60% !important;
                background-color: blueviolet;
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



</x-filament-panels::page.simple>
