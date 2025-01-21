<?php

namespace App\Models;

use App\Models\Term;
use App\Models\User;
use App\Models\Agent;
use App\Models\Staff;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Template;
use App\Models\Admission;
use App\Models\ClassRoom;
use App\Models\Designation;
use App\Models\PaymentType;
use App\Traits\HasFeatures;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Models\Qualification;
use App\Models\SalaryPayment;
use App\Models\AdmLtrTemplate;
use App\Models\AcademicSession;
use App\Models\ExpenseCategory;
use App\Models\VariableTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasFactory;
    use HasFeatures;

    protected $fillable = [
        // Basic Information
        'name',
        'name_ar',
        'slug',

        // Contact Information
        'email',
        'phone',
        'address',
        'website',

        // Location Details
        'state_id',
        'lga_id',
        'postal_code',

        // School Details
        'school_type',
        'curriculum_types',
        'student_capacity',
        'established_year',
        'motto',
        'ownership_type',
        'language_of_instruction',
        'gender_type',

        // Media
        'logo',
        'banner',

        // Business Information
        'registration_number',
        'tax_id',
        'customer_code',

        // Subscription & Agent
        'agent_id',
        'current_plan_id',
        'is_on_trial',
        'trial_ends_at',

        // Academic Configuration
        'academic_period',
        'term_structure',

        // Features & Settings
        'settings',
        'features',
        'configurations',
        'theme_settings',

        // Status & Verification
        'is_verified',
        'verified_at',
        'verified_by',
        'status',

        // Social Media
        'social_links',

        // Contact Person
        'contact_person_name',
        'contact_person_phone',
        'contact_person_email',
        'contact_person_role',

        // Meta Information
        'meta_data',
        'remarks',
    ];

    protected $casts = [
        'curriculum_types' => 'array',
        'settings' => 'array',
        'features' => 'array',
        'configurations' => 'array',
        'theme_settings' => 'array',
        'social_links' => 'array',
        'meta_data' => 'array',
        'is_verified' => 'boolean',
        'is_on_trial' => 'boolean',
        'verified_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'established_year' => 'integer',
    ];

    protected $dates = [
        'verified_at',
        'trial_ends_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'school_user');
    }

    // public function hasActiveSubscription($planId)
    // {
    //     return $this->subscriptions()->active()->where('plan_id', $planId)->exists();
    // }

    public function hasActiveSubscription($planId): bool
    {
        if (!$planId) {
            return false;
        }

        try {
            return $this->subscriptions()
                ->where('plan_id', $planId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })
                ->exists();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error checking active subscription', [
                'school_id' => $this->id,
                'plan_id' => $planId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function currentSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('starts_at')
            ->first();
    }


    public function getLogoUrlAttribute(): string
    {
        return $this->logo
            ? Storage::url($this->logo)
            : asset('img/sch_logo.png');
    }


    public function hasFeature(string $feature): bool
    {
        $subscription = $this->currentSubscription();
        return $subscription ? $subscription->hasFeature($feature) : false;
    }

    public function features(): array
    {
        $subscription = $this->currentSubscription();
        return $subscription ? $subscription->plan->features : [];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'school_id');
    }

    // public function hasHadTrial()
    // {
    //     return $this->subscriptions()->where('plan_id', 1)->exists();
    // }

    public function canRenewSubscription()
    {
        $subscription = $this->subscriptions()->latest('created_at')->first();
        return $subscription && $subscription->status === 'cancelled'; // Adjust condition based on your logic
    }

    public function academicSessions()
    {
        return $this->hasMany(AcademicSession::class);
    }

    public function terms()
    {
        return $this->hasMany(Term::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function Templates()
    {
        return $this->hasMany(Template::class);
    }

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentTypes()
    {
        return $this->hasMany(PaymentType::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function salaries()
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function qualifications()
    {
        return $this->hasMany(Qualification::class);
    }

    public function assessmentTypes()
    {
        return $this->hasMany(AssessmentType::class);
    }

    function GradingScales(): HasMany
    {
        return $this->hasMany(GradingScale::class);
    }

    function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    function reportTemplates(): HasMany
    {
        return $this->hasMany(ReportTemplate::class);
    }

    function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // student movement
    public function studentMovements()
    {
        return $this->hasMany(StudentMovement::class);
    }

    public function admin()
    {
        return $this->belongsToMany(User::class)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'school-admin');
            })
            ->first();
    }

    // In School model
    public function roles()
    {
        return $this->hasMany(Role::class, 'team_id');
    }

    public function schoolSettings()
    {
        return $this->hasOne(SchoolSettings::class);
    }

    /** @return HasMany<\App\Models\ActivityType, self> */
    public function activityTypes(): HasMany
    {
        return $this->hasMany(\App\Models\ActivityType::class);
    }


    /** @return HasMany<\App\Models\BehavioralTrait, self> */
    public function behavioralTraits(): HasMany
    {
        return $this->hasMany(\App\Models\BehavioralTrait::class);
    }


    /** @return HasMany<\App\Models\Book, self> */
    public function books(): HasMany
    {
        return $this->hasMany(\App\Models\Book::class);
    }


    /** @return HasMany<\App\Models\ExpenseItem, self> */
    public function expenseItems(): HasMany
    {
        return $this->hasMany(\App\Models\ExpenseItem::class);
    }



    /** @return HasMany<\App\Models\SchoolCalendarEvent, self> */
    public function schoolCalendarEvents(): HasMany
    {
        return $this->hasMany(\App\Models\SchoolCalendarEvent::class);
    }

    public function settings()
    {
        return $this->hasOne(SchoolSettings::class);
    }

    // Helper method to ensure settings exist
    public function getSettingsAttribute()
    {
        return $this->settings()->firstOrCreate(
            [],
            SchoolSettings::getDefaultSettings()
        );
    }

    public function templateVariables()
    {
        return $this->hasMany(TemplateVariable::class);
    }

    /**
     * Get all super admins for the school
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function superAdmins()
    {
        return $this->members()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'super_admin')
                    ->where('roles.team_id', $this->id);
            });
    }

    /**
     * Get first super admin of the school
     * 
     * @return ?\App\Models\User
     */
    public function getSuperAdmin()
    {
        return $this->superAdmins()->first();
    }

    /**
     * Check if a user is super admin of the school
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    public function isSuperAdmin(User $user): bool
    {
        return $this->superAdmins()
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * Get super admins with their notification preferences eager loaded
     * Useful when sending notifications
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSuperAdminsForNotifications()
    {
        return $this->superAdmins()
            ->with('notificationPreferences')
            ->get();
    }
}
