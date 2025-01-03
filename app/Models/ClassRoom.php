<?php

namespace App\Models;

use App\Models\Book;
use App\Models\Staff;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
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

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_room_subject');
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
