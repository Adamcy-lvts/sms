<?php

// app/Models/Draft.php
namespace App\Models;

use App\Models\User;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Draft extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'type',          // Type of draft (e.g., 'bulk_grades', 'report_card', etc.)
        'content',       // JSON content of the draft
        'metadata',      // Additional metadata
        'last_modified', // Last modification timestamp
        'is_auto_save'   // Whether this was an auto-save
    ];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
        'last_modified' => 'datetime',
        'is_auto_save' => 'boolean',
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function updateContent(array $content, bool $isAutoSave = false): void
    {
        $this->update([
            'content' => $content,
            'last_modified' => now(),
            'is_auto_save' => $isAutoSave
        ]);
    }
}
