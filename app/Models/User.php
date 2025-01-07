<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use App\Models\Agent;
use App\Models\Staff;
use Filament\Facades\Filament;
use App\Models\AttendanceRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Filament\Models\Contracts\HasName;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements HasName, FilamentUser, HasTenants, HasAvatar
{
    use HasRoles, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {

            return ($this->email === 'lv4mj1@gmail.com');
        }

        return true;
    }

    // In User model
    // public function canAccessPanel(Panel $panel): bool
    // {
    //     if ($panel->getId() === 'admin') {
    //         // For admin panel, check only global super admin
    //         return $this->roles()
    //             ->where('roles.name', 'super_admin') // Specify table name
    //             ->whereNull('roles.school_id')
    //             ->exists();
    //     }

    //     // Debug logging
    //     Log::info('Checking panel access', [
    //         'panel' => $panel->getId(),
    //         'user_id' => $this->id,
    //         'roles' => $this->roles()->get()
    //     ]);

    //     if ($panel->getId() === 'sms') {
    //         $schoolId = Filament::getTenant()?->id;
    //         $schoolId = $this->schools->first()->id;
    //         Log::info('User Schools:', [$schoolId]);
    //         $hasAccess = $this->roles()
    //             ->where(function ($query) use ($schoolId) {
    //                 $query->where(function ($q) use ($schoolId) {
    //                     $q->where('roles.name', 'super_admin')
    //                         ->where('roles.school_id', $schoolId);
    //                 })->orWhere(function ($q) {
    //                     $q->where('roles.name', 'super_admin')
    //                         ->whereNull('roles.school_id');
    //                 });
    //             })
    //             ->exists();

    //         Log::info('SMS panel access check', [
    //             'school_id' => $schoolId,
    //             'has_access' => $hasAccess
    //         ]);

    //         return $hasAccess;
    //     }

    //     return true;
    // }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'user_type',
        'email',
        'status_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        // If the user has a profile photo, return it
        if ($this->profile_picture) {
            return asset('storage/' . $this->profile_picture);
        }

        // If not, return the default image
        return asset('img/default.jpg');
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function getShortNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->schools;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->schools()->whereKey($tenant)->exists();
    }
}
