<?php

namespace App\Models;

use App\Models\School;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
