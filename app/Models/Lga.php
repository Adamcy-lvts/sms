<?php

namespace App\Models;

use App\Models\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lga extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'state_id'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
