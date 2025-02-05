<?php

namespace App\Observers;

use App\Models\Staff;
use App\Models\School;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class StaffObserver
{
    public function created(Staff $staff): void
    {
        // if ($staff->user_id) {
        //     $school = School::find($staff->school_id);
        //     $currentCount = Staff::countStaffWithUserAccounts($school->id);
        //     $maxAllowed = $school->currentSubscription?->plan?->max_staff ?? 0;
            
        //     if ($maxAllowed > 0) {
        //         $remaining = max(0, $maxAllowed - $currentCount);
                
        //         if ($remaining <= 5) {
        //             $superAdmin = $school->getSuperAdmin();
        //             if ($superAdmin) {
        //                 Notification::make()
        //                     ->warning()
        //                     ->title('Staff User Account Limit Warning')
        //                     ->body("You have {$remaining} staff user account(s) remaining in your current plan.")
        //                     ->icon('heroicon-o-exclamation-triangle')
        //                     ->actions([
        //                         \Filament\Notifications\Actions\Action::make('view_plans')
        //                             ->button()
        //                             ->url(route('filament.sms.pages.pricing-page', ['tenant' => $school->slug]))
        //                             ->label('View Plans'),
        //                     ])
        //                     ->sendToDatabase($superAdmin);
        //             }
        //         }
        //     }
        // }
    }

    /**
     * Handle the Staff "deleted" event.
     */
    public function deleted(Staff $staff): void
    {
        if ($staff->profile_picture) {
            Storage::disk('public')->delete($staff->profile_picture);
        }

        // Also delete signature if exists
        if ($staff->signature) {
            Storage::disk('public')->delete($staff->signature);
        }
    }

    /**
     * Handle the Staff "updating" event.
     */
    public function updating(Staff $staff): void
    {
        // Check if profile picture is being changed
        if ($staff->isDirty('profile_picture') && $staff->getOriginal('profile_picture')) {
            Storage::disk('public')->delete($staff->getOriginal('profile_picture'));
        }

        // Check if signature is being changed
        if ($staff->isDirty('signature') && $staff->getOriginal('signature')) {
            Storage::disk('public')->delete($staff->getOriginal('signature'));
        }
    }
}
