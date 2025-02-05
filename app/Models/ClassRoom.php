<?php

namespace App\Models;

use App\Models\Book;
use App\Models\Staff;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'level',
        'school_id',
        'capacity',
    ];


    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }


    public function teachers()
    {
        return $this->belongsToMany(Teacher::class);
    }


    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_room_subject');
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    // Add helper method for level
    public function getLevel(): ?string
    {
        // Extract level from class name or use stored level
        return $this->level ?? $this->determineLevelFromName();
    }

    protected function determineLevelFromName(): ?string
    {
        $name = strtolower($this->name);

        if (str_contains($name, 'nursery')) return 'nursery';
        if (str_contains($name, 'primary')) return 'primary';
        if (str_contains($name, 'secondary') || str_contains($name, 'jss') || str_contains($name, 'sss')) {
            return 'secondary';
        }

        return null;
    }
}
