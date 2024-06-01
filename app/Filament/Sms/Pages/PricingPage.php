<?php

namespace App\Filament\Sms\Pages;

use App\Models\Plan;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PricingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.sms.pages.pricing-page';

    protected static bool $shouldRegisterNavigation = false;

    public $pricingPlans;
    public $user;
    public $school;

    public function mount()
    {
        $this->user = Auth::user();
        $this->school = $this->user->schools->first();
        $this->pricingPlans = Plan::all();
    }
}
