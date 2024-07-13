<?php

namespace App\Models;

use App\Models\Lga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function lgas()
    {
        return $this->hasMany(Lga::class);
    }
}
