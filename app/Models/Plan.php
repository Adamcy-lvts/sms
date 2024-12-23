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

    protected $casts = [
        'features' => 'array',
    ];
    

    // Relationship with Subscription
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features) ||
               in_array('All Basic Features', $this->features) ||
               in_array('All Standard Features', $this->features);
    }

    public static function allFeatures(): array
    {
        return self::pluck('features')->flatten()->unique()->values()->toArray();
    }
}
