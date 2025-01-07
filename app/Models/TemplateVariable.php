<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',           // Variable name (e.g., student_name)
        'display_name',   // Human readable name (e.g., Student Name)
        'description',    // Description of what this variable represents
        'category',       // Category of templates this variable belongs to
        'school_id',      // School specific variables
        'field_type',     // Type of field (text, date, number etc)
        'sample_value',   // Sample value for preview
        'is_system',      // Whether it's a system variable or custom
        'is_active',      // Enable/disable variables
        'mapping',        // Database field mapping if applicable
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

     // Define available field types
     public static function fieldTypes(): array 
     {
         return [
             'text' => 'Text',
             'date' => 'Date',
             'number' => 'Number',
             'email' => 'Email',
             'phone' => 'Phone Number',
             'address' => 'Address',
             'currency' => 'Currency',
             'boolean' => 'Yes/No'
         ];
     }
 
     // Define categories
     public static function categories(): array
     {
         return [
             'all' => 'All Documents',
             'admission' => 'Admission Documents',
             'academic' => 'Academic Documents', 
             'financial' => 'Financial Documents',
             'general' => 'General Documents'
         ];
     }
 
     // Scopes for filtering
     public function scopeActive($query)
     {
         return $query->where('is_active', true);
     }
 
     public function scopeByCategory($query, $category)
     {
         return $query->where('category', $category);
     }
 
  
    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
