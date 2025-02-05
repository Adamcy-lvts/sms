<?php

namespace App\Filament\Sms\Pages;

use App\Models\Plan;
use App\Models\School;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class PricingPage extends Page
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Billing & Subscriptions';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.sms.pages.pricing-page';

    protected static bool $shouldRegisterNavigation = false;

    // public $pricingPlans;
    public $user;
    // public $school;
    public $tenant;
    
    public bool $isAnnual = false;
    public ?Collection $pricingPlans = null;
    public ?School $school = null;
    protected $errorMessage = null;

    public function mount()
    {
        try {
            $this->school = Filament::getTenant();
            $this->loadPlans();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading pricing page', [
                'error' => $e->getMessage()
            ]);
            $this->errorMessage = 'Unable to load pricing plans. Please try again later.';
        }
    }

    protected function loadPlans()
    {
        $this->pricingPlans = $this->isAnnual 
            ? Plan::with('features')->annually()->active()->get()
            : Plan::with('features')->monthly()->active()->get();
    }

    public function toggleBilling(): void
    {
        $this->isAnnual = !$this->isAnnual;
        $this->loadPlans();
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
