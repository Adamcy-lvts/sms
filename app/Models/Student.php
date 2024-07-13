<?php

namespace App\Models;

use App\Models\User;
use App\Models\School;
use App\Models\Status;
use App\Models\Admission;
use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'class_room_id', 'user_id', 'status_id', 'admission_id', 'identification_number', 'admission_number', 'first_name', 'last_name', 'middle_name', 'phone_number', 'date_of_birth', 'profile_picture'
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
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
}
