<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\AcademicSession;
use App\Models\Term;
use Filament\Facades\Filament;

class CurrentAcademicInfo extends Component
{
    public ?AcademicSession $currentSession = null;
    public ?Term $currentTerm = null;

    public function mount()
    {
        $tenant = Filament::getTenant();
        
        if ($tenant) {
            $this->currentSession = $tenant->academicSessions()
                ->where('is_current', true)
                ->first();
                
            if ($this->currentSession) {
                $this->currentTerm = $this->currentSession->terms()
                    ->where('is_current', true)
                    ->first();
            }
        }
    }

    public function render()
    {
        return view('livewire.current-academic-info', [
            'hasAcademicPeriod' => !is_null($this->currentSession) || !is_null($this->currentTerm)
        ]);
    }
}