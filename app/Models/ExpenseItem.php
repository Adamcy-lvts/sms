<?php

namespace App\Models;

use App\Models\School;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    protected $fillable = [
        'school_id',
        'expense_category_id',
        'name',
        'description',
        'default_amount', // Unit price
        'unit', // e.g., 'piece', 'rim', 'box', 'carton'
        'minimum_quantity', // Reorder threshold
        'is_stock_tracked', // Whether we track inventory
        'current_stock', // Current quantity in stock
        'is_recurring',
        'frequency',
        'is_active',
        'specifications', // JSON field for additional details
        'last_purchase_date',
        'last_purchase_price'
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_stock_tracked' => 'boolean',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'current_stock' => 'integer',
        'minimum_quantity' => 'integer',
        'specifications' => 'array',
        'last_purchase_date' => 'datetime',
        'last_purchase_price' => 'decimal:2'
    ];

    // Add helpful accessors
    public function getStockStatusAttribute(): string
    {
        if (!$this->is_stock_tracked) {
            return 'Not Tracked';
        }

        if ($this->current_stock <= $this->minimum_quantity) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    public function scopeLowStock($query)
    {
        return $query->where('is_stock_tracked', true)
            ->whereRaw('current_stock <= minimum_quantity');
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return formatNaira($this->default_amount) . " per {$this->unit}";
    }

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    // Scopes for filtering
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
