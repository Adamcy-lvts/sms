<?php

namespace App\Models;

use App\Models\Lga;
use App\Models\State;
use App\Models\School;
use App\Models\Status;
use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_session_id',
        'session',
        'first_name',
        'full_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'address',
        'phone_number',
        'email',
        'nationality',
        'state_id',
        'lga_id',
        'religion',
        'blood_group',
        'genotype',
        'previous_school_name',
        'previous_class',
        'application_date',
        'admitted_date',
        'admission_number',
        'status_id',
        'guardian_name',
        'guardian_relationship',
        'guardian_phone_number',
        'guardian_email',
        'guardian_address',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone_number',
        'emergency_contact_email',
        'disability_type',
        'disability_description',
        'passport_photograph',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'application_date' => 'date',
        'admitted_date' => 'date'
    ];

    public function getFullNameAttribute(): string
    {
        // First try to get the full_name column
        if (!empty($this->full_name)) {
            return $this->full_name;
        }

        // Fall back to constructing from individual fields
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
