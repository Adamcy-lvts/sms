<?php

namespace App\Console\Commands;

use App\Models\Term;
use App\Models\School;
use App\Models\AcademicSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateAcademicPeriod extends Command
{
    protected $signature = 'academic:update-period';
    protected $description = 'Update current academic session and term based on date';

    public function handle()
    {
        $this->info('Updating academic periods...');

        try {
            // Get all schools - assuming you're using multi-tenancy
            $schools = School::all();

            foreach ($schools as $school) {
                $this->updateCurrentSession($school);
                $this->updateCurrentTerm($school);

                // Clear cache for this school
                Cache::tags(["school:{$school->slug}"])->flush();

                $this->info("Cleared cache for school: {$school->name}");
            }

            $this->info('Academic periods updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update academic periods: ' . $e->getMessage());
        }
    }

    protected function updateCurrentSession($school)
    {
        $now = now();

        $this->info("\nProcessing school: {$school->name}");

        $currentSession = $school->academicSessions()
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        if ($currentSession) {
            // Check if this session is already correctly set as current
            $alreadyCurrent = $school->academicSessions()
                ->where('id', $currentSession->id)
                ->where('is_current', true)
                ->exists();

            if ($alreadyCurrent) {
                $this->info("Session {$currentSession->name} is already current. Skipping update.");
                return;
            }

            try {
                // Reset all current flags and set new current session
                $school->academicSessions()
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                $currentSession->update(['is_current' => true]);
                $this->info("Set current session to: {$currentSession->name}");
            } catch (\Exception $e) {
                $this->error("Error updating session: " . $e->getMessage());
            }
        } else {
            $this->warn("No active session found for dates around: {$now}");
        }
    }

    protected function updateCurrentTerm($school)
    {
        $now = now();

        $currentSession = $school->academicSessions()
            ->where('is_current', true)
            ->first();

        if ($currentSession) {
            $currentTerm = $school->terms()
                ->where('academic_session_id', $currentSession->id)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->first();

            if ($currentTerm) {
                // Check if this term is already correctly set as current
                $alreadyCurrent = $school->terms()
                    ->where('id', $currentTerm->id)
                    ->where('is_current', true)
                    ->exists();

                if ($alreadyCurrent) {
                    $this->info("Term {$currentTerm->name} is already current. Skipping update.");
                    return;
                }

                try {
                    // Reset all current flags and set new current term
                    $school->terms()
                        ->where('is_current', true)
                        ->update(['is_current' => false]);

                    $currentTerm->update(['is_current' => true]);
                    $this->info("Set current term to: {$currentTerm->name}");
                } catch (\Exception $e) {
                    $this->error("Error updating term: " . $e->getMessage());
                }
            } else {
                $this->warn("No active term found for the current date");
            }
        } else {
            $this->warn("No current academic session found");
        }
    }
}
