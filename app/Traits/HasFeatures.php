<?php

namespace App\Traits;

use App\Models\Plan;

trait HasFeatures
{
    public function hasFeature(string $feature): bool
    {
        if (!in_array($feature, Plan::allFeatures())) {
            throw new \InvalidArgumentException("Invalid feature: $feature");
        }
        return method_exists($this, 'currentSubscription') ? 
            $this->currentSubscription()?->hasFeature($feature) ?? false : 
            false;
    }

    public function features(): array
    {
        return method_exists($this, 'currentSubscription') ? 
            $this->currentSubscription()?->plan->features ?? [] : 
            [];
    }
}