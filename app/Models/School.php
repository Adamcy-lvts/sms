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
        'name',
        'email',
        'slug',
        'address',
        'agent_id',
        'phone',
        'logo',
        'settings'
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'school_user');
    }

    public function hasActiveSubscription($planId)
    {
        return $this->subscriptions()->active()->where('plan_id', $planId)->exists();
    }

    public function currentSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('starts_at')
            ->first();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(SchoolSettings::class);
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

    public function variableTemplates()
    {
        return $this->hasMany(VariableTemplate::class);
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

    function subjectAssessments(): HasMany
    {
        return $this->hasMany(SubjectAssessment::class);
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
}
