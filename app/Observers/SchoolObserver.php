<?php

namespace App\Observers;

use App\Models\School;
use Illuminate\Support\Facades\Storage;

class SchoolObserver
{
    /**
     * Handle the School "deleted" event.
     */
    public function deleted(School $school): void
    {
        if ($school->logo) {
            Storage::disk('public')->delete($school->logo);
        }

        if ($school->banner) {
            Storage::disk('public')->delete($school->banner);
        }
    }

    /**
     * Handle the School "updating" event.
     */
    public function updating(School $school): void
    {
        // Check if logo is being changed
        if ($school->isDirty('logo') && $school->getOriginal('logo')) {
            Storage::disk('public')->delete($school->getOriginal('logo'));
        }

        // Check if banner is being changed
        if ($school->isDirty('banner') && $school->getOriginal('banner')) {
            Storage::disk('public')->delete($school->getOriginal('banner'));
        }
    }
}
