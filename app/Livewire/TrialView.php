<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;

class TrialView extends Component
{

    public $user;
    public $school;
    public $latestSubscription;

    public function mount(): void
    {
        $this->user = auth()->user();

        $this->school = $this->user->schools->first();

        $this->latestSubscription = $this->school->subscriptions()->where('status','active')->latest()->first();
    }




 
    public function getDaysLeftInTrialProperty()
    {
        

        if ($this->latestSubscription && $this->latestSubscription->is_on_trial && $this->latestSubscription->trial_ends_at) {
            $trialEndDate = Carbon::parse($this->latestSubscription->trial_ends_at);
            $currentDate = Carbon::now();

            // Calculate the difference in days
            $daysLeft = $currentDate->diffInDays($trialEndDate, false);

            // Ensure the result is always a non-negative integer
            return intval($daysLeft); // Convert to integer
        }

        return 0; // Default to 0 if there's no trial, trial_end_at is null, or if it's not accessible
    }
    
    

    

    public function render()
    {
        return view('livewire.trial-view');
    }
}
