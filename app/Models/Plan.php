<?php

namespace App\Models;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'price',
        'description',
        'duration',
        'features', // if you want to allow mass assigning features
        'cto'       // if you want to allow mass assignment for call to action
    ];

    // Relationship with Subscription
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
