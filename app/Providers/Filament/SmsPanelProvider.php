<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\School;
use Filament\PanelProvider;
use Filament\Facades\Filament;

use App\Livewire\ReportProgress;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Blade;
use App\Filament\Sms\Pages\PaymentForm;
use App\Filament\Sms\Pages\PricingPage;
use App\Filament\Sms\Pages\Auth\Register;
use App\Http\Middleware\SchoolPermission;
use App\Filament\Sms\Pages\ManageSettings;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\SetAcademicPeriod;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Facades\FilamentView;
use App\Filament\Sms\Pages\Tenancy\Billing;
use App\Filament\Sms\Pages\Auth\EditProfile;
use App\Filament\Sms\Billing\BillingProvider;
use App\Filament\Sms\Widgets\RevenueBarChart;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Sms\Widgets\FilamentInfoWidget;
use App\Filament\Sms\Widgets\RevenueByTypeChart;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Sms\Widgets\SchoolStatsOverview;
use App\Filament\Sms\Pages\Tenancy\EditSchoolProfile;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class SmsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sms')
            ->path('sms')
            ->login()
            ->brandName(function () {
                $tenant = Filament::getTenant();
                return $tenant ? "{$tenant->name}" : 'School SMS';
            })
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
            ->tenantMenu(fn() => auth()->user()->can('view_tenant_menu'))
            ->userMenuItems([
                MenuItem::make()
                    ->label('Settings')
                    ->url(function () {
                        $tenant = Filament::getTenant();
                        
                        if (!$tenant) {
                            return '#'; // or handle the no-tenant case differently
                        }

                        return ManageSettings::getUrl([
                            'tenant' => $tenant->slug
                        ]);
                    })->visible(fn() => auth()->user()->can('view_tenant_menu'))
                    ->icon('heroicon-o-cog-6-tooth'),

            ])
            ->tenantMiddleware([
                \BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant::class,
                ApplyTenantScopes::class,
                SetAcademicPeriod::class,
            ], isPersistent: true)
            ->colors([
                'primary' => Color::Emerald,
                'danger' => Color::Red,
                'gray' => Color::Zinc,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'indigo' => Color::Indigo,
            ])
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Sms/Resources'), for: 'App\\Filament\\Sms\\Resources')
            ->discoverPages(in: app_path('Filament/Sms/Pages'), for: 'App\\Filament\\Sms\\Pages')
            ->pages([
                Pages\Dashboard::class,
                PaymentForm::class,
                // ReportProgress::class,
                // Pages\PreviewReportTemplate::class,
                // Pages\ManageSettings::class,
            ])
            ->viteTheme('resources/css/filament/sms/theme.css')
            ->discoverWidgets(in: app_path('Filament/Sms/Widgets'), for: 'App\\Filament\\Sms\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                FilamentInfoWidget::class,
                RevenueBarChart::class,
                RevenueByTypeChart::class,
                SchoolStatsOverview::class,
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
                // \App\Http\Middleware\SetAcademicPeriod::class,
                \App\Http\Middleware\CheckUserStatus::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->registration(Register::class)
            ->profile(EditProfile::class)
            ->passwordReset()
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn(): string => Blade::render('@livewire(\'current-academic-info\')')
            )
            // ->renderHook(
            //     PanelsRenderHook::BODY_START,
            //     fn(): string => Blade::render('@livewire(\'report-progress\')')
            // )
            ->spa();
    }
}
