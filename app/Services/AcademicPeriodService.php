<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AcademicPeriodService
{
    public function updateCurrentPeriod()
    {
        DB::transaction(function () {
            $this->updateCurrentSession();
            $this->updateCurrentTerm();
        });
    }

    private function updateCurrentSession()
    {
        $currentSession = AcademicSession::getCurrentSession();
        
        if ($currentSession) {
            AcademicSession::where('is_current', true)->update(['is_current' => false]);
            $currentSession->update(['is_current' => true]);
        }
    }

    private function updateCurrentTerm()
    {
        $currentTerm = Term::getCurrentTerm();
        
        if ($currentTerm) {
            Term::where('is_current', true)->update(['is_current' => false]);
            $currentTerm->update(['is_current' => true]);
        }
    }
}