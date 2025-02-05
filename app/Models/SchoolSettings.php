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
            "format_type": "school_session",
            "custom_format": null,
            "school_prefix": null,
            "length": 4,
            "separator": "/",
            "include_separator": true,
            "include_prefix": true,
            "initials_method": "first_letters",
            "session_format": "full_session",
            "number_start": 1,
            "reset_sequence_by_session": true,
            "include_session": true,
            "custom_session": null,
            "manual_numbering": false,
            "show_last_number": true,
            "default_session_format": "short_session"
        }',

        // Employee Settings
        'employee_settings' => '{
            "prefix_type": "consonants",
            "include_prefix": true,
            "include_year": true,
            "include_separator": true,
            "year_format": "short",
            "separator": "/",
            "number_length": 3,
            "custom_year": null
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

    // Add a method to get merged settings with defaults
    public function getMergedSettings(string $type): array
    {
        $defaults = json_decode(static::$defaultAttributes["{$type}_settings"], true);
        $current = $this->{"{$type}_settings"} ?? [];

        return array_merge($defaults, $current);
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
