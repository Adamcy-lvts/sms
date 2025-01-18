<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SystemAnnouncementDismissal;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'background_color',
        'text_color',
        'is_active',
        'is_dismissible',
        'starts_at',
        'ends_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime'
    ];

    // Get active announcements
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    // Check if user has dismissed this announcement
    public function isDismissedByUser($userId): bool
    {
        return $this->dismissals()
            ->where('user_id', $userId)
            ->exists();
    }

    public function dismissals()
    {
        return $this->hasMany(SystemAnnouncementDismissal::class);
    }
}
