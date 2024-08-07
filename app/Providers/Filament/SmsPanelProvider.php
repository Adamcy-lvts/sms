<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\School;

use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use App\Filament\Sms\Pages\PricingPage;
use App\Filament\Sms\Pages\Auth\Register;
use App\Http\Middleware\ApplyTenantScopes;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Facades\FilamentView;
use App\Filament\Sms\Pages\Tenancy\Billing;
use App\Filament\Sms\Pages\Auth\EditProfile;
use App\Filament\Sms\Billing\BillingProvider;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Sms\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Sms\Pages\Tenancy\EditSchoolProfile;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class SmsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sms')
            ->path('sms')
            ->login()
            ->brandName('Kings Private School')
            ->tenant(School::class, slugAttribute: 'slug')
            ->tenantProfile(EditSchoolProfile::class)
            ->tenantBillingProvider(new BillingProvider())
            ->requiresTenantSubscription()
            ->tenantBillingRouteSlug('billing')
            ->tenantMenuItems([
                'billing' => MenuItem::make()->label('Manage subscription'),
                MenuItem::make()
                    ->label('Pricing')
                    ->url(function (): string {
                        return PricingPage::getUrl();
                    })
                    ->icon('heroicon-o-currency-dollar'),
            ])
            ->tenantMiddleware([
                ApplyTenantScopes::class,
            ], isPersistent: true)
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Sms/Resources'), for: 'App\\Filament\\Sms\\Resources')
            ->discoverPages(in: app_path('Filament/Sms/Pages'), for: 'App\\Filament\\Sms\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->viteTheme('resources/css/filament/sms/theme.css')
            ->discoverWidgets(in: app_path('Filament/Sms/Widgets'), for: 'App\\Filament\\Sms\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->registration(Register::class)
            ->profile(EditProfile::class)
            ->passwordReset()
            ->renderHook(

                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@livewire(\'trial-view\')'),

            );
    }
}
