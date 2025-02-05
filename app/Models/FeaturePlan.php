<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturePlan extends Model
{
    protected $table = 'feature_plan';

    protected $fillable = ['feature_id', 'plan_id', 'limits'];

    public $timestamps = false;

    // cast

    protected $casts = [
        'limits' => 'array',
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function getLimitsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    public function setLimitsAttribute($value)
    {
        $this->attributes['limits'] = is_array($value) ? json_encode($value) : $value;
    }
}
