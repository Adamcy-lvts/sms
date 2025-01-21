<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'school_id',
        'inventory_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'note',
        'created_by'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
