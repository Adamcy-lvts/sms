<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'payment_type_id',
        'name',
        'code',
        'description',
        'quantity',
        'unit_price',
        'selling_price',
        'reorder_level',
        'is_active',
        'meta_data'
    ];

    protected $casts = [
        'meta_data' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // Helper methods
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->quantity >= $quantity;
    }
}
