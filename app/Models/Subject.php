<?php

namespace App\Models;

use App\Models\Book;
use App\Models\School;
use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'school_id',
        'position',
        'color',
        'icon',
        'description',
        'is_optional',
        'is_active',
        'is_archived',
        'name_ar',
        'description_ar'
    ];

    public function getNameAttribute($value)
    {
        if (
            request()->hasHeader('Accept-Language') &&
            request()->header('Accept-Language') === 'ar' &&
            $this->name_ar
        ) {
            return $this->name_ar;
        }
        return $value;
    } 

    public function school()
    {
        return $this->belongsTo(School::class);
    }


    public function classRooms()
    {
        return $this->belongsToMany(ClassRoom::class, 'class_room_subject');
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class);
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
