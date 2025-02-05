<?php

namespace App\Models;

use App\Models\Bank;
use App\Models\User;
use App\Models\School;
use App\Models\Status;
use App\Models\Teacher;
use App\Models\Designation;
use App\Models\Qualification;
use App\Models\SalaryPayment;
use Filament\Facades\Filament;
use App\Services\FeatureService;
use App\Services\EmployeeIdGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'designation_id',
        'employee_id',
        'status_id',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'date_of_birth',
        'phone_number',
        'email',
        'address',
        'hire_date',
        'employment_status',
        'salary',
        'bank_id',
        'account_number',
        'account_name',
        'profile_picture',
        'emergency_contact',
        'signature',
    ];

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($staff) {
    //         if (empty($staff->employee_id)) {
    //             $tenant = Filament::getTenant();
    //             $settings = $tenant->getSettingsAttribute();
    //             $generator = new EmployeeIdGenerator($settings);
                
    //             $staff->employee_id = $generator->generate([
    //                 'id_format' => $settings->employee_id_format_type,
    //                 'designation_id' => $staff->designation_id,
    //             ]);
    //         }
    //     });
    // }

    // protected static function booted(): void
    // {
    //     static::updated(function (Staff $staff) {
    //         // Check if user_id was added (staff member got a user account)
    //         if ($staff->wasChanged('user_id') && $staff->user_id !== null) {
    //             $school = $staff->school;
    //             $featureService = app(FeatureService::class);
    //             $result = $featureService->checkStaffUserLimit($school, $school->currentSubscription->plan);

    //             if ($result->status === 'warning') {
    //                 $superAdmin = $school->getSuperAdmin();
    //                 if ($superAdmin) {
    //                     Notification::make()
    //                         ->warning()
    //                         ->title('Staff User Account Limit Warning')
    //                         ->body($result->message)
    //                         ->icon('heroicon-o-exclamation-triangle')
    //                         ->actions([
    //                             \Filament\Notifications\Actions\Action::make('view_plans')
    //                                 ->button()
    //                                 ->url(route('filament.sms.pages.pricing-page', ['tenant' => $school->slug]))
    //                                 ->label('View Plans'),
    //                         ])
    //                         ->sendToDatabase($superAdmin);
    //                 }
    //             }
    //         }
    //     });
    // }

    public function getProfilePictureUrlAttribute()
    {
        // If the student has a profile photo, return it
        if ($this->profile_picture && Storage::disk('public')->exists($this->profile_picture)) {
            return asset('storage/' . $this->profile_picture);
        }

        // If not, return the default image
        return asset('img/default.jpg');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function qualifications()
    {
        return $this->hasMany(Qualification::class);
    }

    public function salaryHistory()
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public static function countStaffWithUserAccounts(int $schoolId): int
    {
        return static::where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->count();
    }

    public static function hasReachedUserAccountLimit(School $school): bool
    {
        $currentCount = static::countStaffWithUserAccounts($school->id);
        $maxAllowed = $school->currentSubscription?->plan?->max_staff ?? 0;
        
        return $maxAllowed > 0 && $currentCount >= $maxAllowed;
    }
}
