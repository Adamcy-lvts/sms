<?php

namespace App\Livewire;

use App\Models\Plan;
use Livewire\Component;
use Livewire\Attributes\Computed;

class PricingComponent extends Component
{
    // Public properties
    public bool $isAnnual = false;
    public ?string $selectedPlan = null;

    // Properties to cache plan features
    private $monthlyPlansCache;
    private $annualPlansCache;

    // Constructor to initialize caches
    public function boot()
    {
        $this->monthlyPlansCache = Plan::monthly()->active()->get();
        $this->annualPlansCache = Plan::annually()->active()->get();
    }

    // Computed property for plans
    #[Computed]
    public function pricingPlans()
    {
        return $this->isAnnual ? $this->annualPlansCache : $this->monthlyPlansCache;
    }

    // Toggle billing cycle
    public function toggleBilling(): void
    {
        $this->isAnnual = !$this->isAnnual;
    }

    // Helper method to format price
    public function formatPrice(Plan $plan): string
    {
        $price = $this->isAnnual && $plan->discounted_price !== null 
            ? $plan->discounted_price 
            : $plan->price;

        return formatNaira($price);
    }

    // Action to select a plan
    public function selectPlan(string $planId): void
    {
        $this->selectedPlan = $planId;
        
        // Redirect to registration with plan parameters
        $billingCycle = $this->isAnnual ? 'annual' : 'monthly';
        redirect()->route('register', [
            'plan' => $planId,
            'billing' => $billingCycle
        ]);
    }

    public function render()
    {
        return view('livewire.pricing-component', [
            'plans' => $this->pricingPlans(),
        ]);
    }
}