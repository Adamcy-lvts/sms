<?php

namespace App\Models;

use App\Models\User;
use App\Models\School;
use App\Models\Status;
use App\Models\Payment;
use App\Models\Admission;
use App\Models\ClassRoom;
use Illuminate\Support\Str;
use App\Models\StudentGrade;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_room_id',
        'user_id',
        'status_id',
        'admission_id',
        'identification_number',
        'admission_number',
        'first_name',
        'last_name',
        'middle_name',
        'phone_number',
        'date_of_birth',
        'profile_picture',
        'created_by',
    ];

    // ... your existing methods ...

    /**
     * Get all payments for the student
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getProfilePictureUrlAttribute()
    {
        // If the student has a profile photo, return it
        if ($this->profile_picture && Storage::disk('public')->exists($this->profile_picture)) {
            return asset('storage/' . $this->profile_picture);
        }

        // If not, return the default image
        return asset('img/default.jpg');
    }

    public function getFullNameAttribute(): string
    {
        // First try to get the full_name column
        if (!empty($this->full_name)) {
            return $this->full_name;
        }

        // Fall back to constructing from individual fields
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getShortNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // Existing relationships...
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function currentClass()
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    /**
     * Get the student's current payment status
     */
    public function getPaymentStatusAttribute(): string
    {
        $latestPayment = $this->payments()->latest()->first();
        return $latestPayment?->status?->name ?? 'No Payments';
    }

    /**
     * Get the total amount paid by the student
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('deposit');
    }

    /**
     * Get the total balance remaining for the student
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->payments()->sum('balance');
    }

    // In Student model
    public function grades()
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function termActivities()
    {
        return $this->hasMany(StudentTermActivity::class);
    }

    public function termTraits()
    {
        return $this->hasMany(StudentTermTrait::class);
    }

    public function termComments()
    {
        return $this->hasMany(StudentTermComment::class);
    }

    public function getCurrentTermActivitiesAttribute()
    {
        return $this->termActivities()
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->get();
    }

    public function getCurrentTermTraitsAttribute()
    {
        return $this->termTraits()
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->get();
    }

    public function getCurrentTermCommentAttribute()
    {
        return $this->termComments()
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->first();
    }

    public function statusChanges()
    {
        return $this->morphMany(StatusChange::class, 'statusable');
    }

    public function createUser(): User
    {
        // Generate a random password
        $password = Str::random(10);

        // get status from the status table status of type user and active
        $status = Status::where('type', 'user')->where('name', 'active')->first();

        // Create the user
        $user = User::create([
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->admission->email ?? $this->admission->guardian_email,
            'password' => Hash::make($password),
            'user_type' => 'student',
            'status_id' => $status->id,
        ]);

        // Assign the student role
        // $user->assignRole('student');

        // Associate user with school
        $user->schools()->attach($this->school_id);

        // TODO: Send email with credentials to student/guardian

        return $user;
    }

    protected function generateEmail(): string
    {
        $baseEmail = Str::slug($this->admission_number) . '@' . $this->school->domain;
        $email = $baseEmail;
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = Str::replaceLast('@', $counter . '@', $baseEmail);
            $counter++;
        }

        return $email;
    }
}
