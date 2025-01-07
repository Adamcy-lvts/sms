<?php

namespace App\Models;

use App\Models\School;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public function school()
    {
        return $this->belongsTo(School::class, 'team_id');
    }
}
