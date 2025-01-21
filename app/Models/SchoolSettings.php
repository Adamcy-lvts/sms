<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSettings extends Model
{
    /** Cache settings */
    protected $cacheKey = 'school_settings';
    protected $cacheTTL = 86400; // 24 hour cache duration

    protected $fillable = [
        'school_id',
        'employee_settings',
        'academic_settings',
        'admission_settings',
        'payment_settings',  // Add this
      
    ];

    // Default settings structure
    protected static $defaultAttributes = [
        // Admission Settings
        'admission_settings' => '{
            "format_type": "with_year",
            "custom_format": null,
            "prefix": "ADM",
            "length": 4,
            "separator": "/",
            "school_initials": null,
            "initials_method": "first_letters",
            "session_format": "short",
            "number_start": 1,
            "reset_sequence_yearly": false,
            "reset_sequence_by_session": false
        }',

        // Employee Settings
        'employee_settings' => '{
            "format_type": "basic",
            "custom_format": null,
            "prefix": "EMP",
            "prefix_type": "default",
            "year_format": "short",
            "number_length": 3,
            "separator": "-",
            "department_prefixes": {},
            "separator_rules": {
                "enabled": true,
                "preserve_year": true,
                "preserve_number": true
            }
        }',

        // Academic Settings
        'academic_settings' => '{
            "auto_set_period": true,
            "allow_overlap": false,
            "year_start_month": 9,
            "year_end_month": 7,
            "term_duration": 3,
            "use_arabic": false
        }',

        'payment_settings' => '{
            "due_dates": {
                "term_payment_types": {
                    "school_fees": 30,
                    "computer_fees": 14,
                    "lab_fees": 14
                },
                "default_days": 7
            },
            "allow_session_payment": true
        }',

      
    ];

    protected $casts = [
        'employee_settings' => 'array',
        'academic_settings' => 'array',
        'admission_settings' => 'array',
        'payment_settings' => 'array',
       
       
    ];

    // Boot method for cache handling
    protected static function booted()
    {
        static::saved(function ($settings) {
            Cache::tags(["school:{$settings->school->slug}"])
                ->forget($settings->getCacheKey());
        });
    }

    // Cache key generator
    public function getCacheKey(): string
    {
        return "{$this->cacheKey}:{$this->school_id}";
    }

    // Static getter for settings
    public static function getSettings($schoolId)
    {
        $school = School::find($schoolId);
        return Cache::tags(["school:{$school->slug}"])->remember(
            "school_settings:{$schoolId}",
            86400,
            fn() => self::firstOrCreate(
                ['school_id' => $schoolId],
                self::getDefaultSettings()
            )
        );
    }

    // Default settings generator
    protected static function getDefaultSettings(): array
    {
        return [
            'admission_settings' => json_decode(static::$defaultAttributes['admission_settings'], true),
            'employee_settings' => json_decode(static::$defaultAttributes['employee_settings'], true),
            'academic_settings' => json_decode(static::$defaultAttributes['academic_settings'], true),
            'payment_settings' => json_decode(static::$defaultAttributes['payment_settings'], true),
            
        ];
    }

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Helper methods for services
    public function getAdmissionSettings(): array
    {
        return $this->admission_settings;
    }

    public function getEmployeeSettings(): array
    {
        return $this->employee_settings;
    }

    public function updateSettings(string $type, array $settings): void
    {
        $settingsField = "{$type}_settings";
        $this->$settingsField = array_merge($this->$settingsField ?? [], $settings);
        $this->save();
    }


    // Add helper method for payment settings
    public function getPaymentSettings(): array
    {
        return $this->payment_settings;
    }

    // Add helper method to get due date for a payment type
    public function getDueDate(PaymentType $paymentType, Term $term): ?Carbon
    {
        $settings = $this->payment_settings['due_dates'] ?? [];
        $daysAfterStart = $settings['term_payment_types'][$paymentType->code] 
            ?? $settings['default_days'] 
            ?? null;
        
        return $daysAfterStart ? $term->start_date->addDays($daysAfterStart) : null;
    }

    // Add helper method for session payment check
    public function allowsSessionPayment(): bool
    {
        return $this->payment_settings['allow_session_payment'] ?? false;
    }
}