<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Designation;
use App\Settings\AppSettings;
use App\Models\SchoolSettings;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Employee ID Generator Service
 * 
 * This service generates unique employee IDs for schools based on configurable formats.
 * Each school (tenant) can have their own format and settings.
 * 
 * Format Examples:
 * - Basic: "EMP001" 
 * - With Year: "EMP23001"
 * - With Department: "ADM23001"
 * - Custom: User defined with placeholders
 */

class EmployeeIdGenerator
{

    protected $tenant;
    protected $staff;
    protected $settings;

    /**
     * Constructor initializes the generator with school settings
     * Gets current school context from Filament framework
     */

    public function __construct($settings = null)
    {
        $this->tenant = Filament::getTenant();
        $this->staff = app(Staff::class);

        // Handle settings initialization more safely
        if ($settings) {
            $this->settings = $settings;
        } elseif ($this->tenant) {
            $this->settings = $this->tenant->getSettingsAttribute();
        } else {
            // Provide default settings when no tenant is available
            $this->settings = new SchoolSettings([
                'employee_settings' => [
                    'format_type' => 'school_initials',
                    'prefix_type' => 'school',
                    'year_format' => 'short',
                    'number_length' => 3,
                    'separator' => '/',
                    'session_format' => 'short',
                    'reset_sequence_yearly' => true
                ]
            ]);
        }
    }

    // In EmployeeIdGenerator.php and AdmissionNumberGenerator.php

    protected function getSettings()
    {
        if (!$this->tenant) {
            return $this->settings;
        }

        return Cache::tags(["school:{$this->tenant->slug}"])
            ->remember(
                "school_settings:{$this->tenant->id}",
                86400,
                fn() => $this->tenant->getSettingsAttribute()
            );
    }

    // In EmployeeIdGenerator.php

    // In EmployeeIdGenerator.php
    protected function getFormat(array $data): string
    {
        $settings = $this->settings->employee_settings;
        $formatType = $settings['format_type'] ?? 'basic';
        $numLength = $settings['number_length'] ?? 3;

        return match ($formatType) {
            'basic' => "{PREFIX}{NUM:$numLength}",
            'with_year' => "{PREFIX}{YY}{NUM:$numLength}",
            'with_department' => "{DEPT}{YY}{NUM:$numLength}",
            'custom' => $settings['custom_format'] ?? "{PREFIX}{NUM:$numLength}",
            default => "{PREFIX}{NUM:$numLength}"
        };
    }

    protected function getFormatByType(?string $type, array $data): string
    {
        $type = $type ?? 'basic';
        $numLength = $this->settings->employee_settings['number_length'] ?? 3;

        return match ($type) {
            'basic' => "{PREFIX}{NUM:$numLength}",
            'with_year' => "{PREFIX}{YY}{NUM:$numLength}",
            'with_department' => "{DEPT}{YY}{NUM:$numLength}",
            'custom' => $this->settings->employee_settings['custom_format'] ?? "{PREFIX}{NUM:$numLength}",
            default => "{PREFIX}{NUM:$numLength}"
        };
    }

    protected function parseFormat(string $format, array $data): string
    {
        $settings = $this->settings->employee_settings;
        $prefix = $this->getPrefix($settings);
        $separator = $settings['separator'] ?? '';
        $numLength = $settings['number_length'] ?? 3;
        $yearFormat = $settings['year_format'] ?? 'short';

        // Get year based on format
        $year = match ($yearFormat) {
            'full' => date('Y'),
            'short' => date('y'),
            default => ''
        };

        // Replace format tokens
        $replacements = [
            '{PREFIX}' => $prefix,
            '{YY}' => date('y'),
            '{YYYY}' => date('Y'),
            '{DEPT}' => $this->getDepartmentPrefix($data),
            "{NUM:$numLength}" => $this->getNextNumber($format),
        ];

        $result = str_replace(array_keys($replacements), array_values($replacements), $format);

        // Handle separator and preserve numbers
        if ($separator) {
            $parts = [];
            $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $result);

            // Split based on format type and year format
            if (str_contains($format, '{YY}') || str_contains($format, '{YYYY}')) {
                // Find prefix part
                $yearPos = strpos($cleanId, $year);
                if ($yearPos !== false) {
                    $parts[] = substr($cleanId, 0, $yearPos);
                    $parts[] = $year;
                    $parts[] = substr($cleanId, $yearPos + strlen($year));
                }
            } else {
                // Split into standard parts
                $prefixPart = substr($cleanId, 0, strlen($prefix));
                $numberPart = substr($cleanId, -$numLength);
                $parts = [$prefixPart, $numberPart];
            }

            // Filter out empty parts and join with separator
            $result = implode($separator, array_filter($parts));
        }

        return $result;
    }

    protected function getPrefix(array $settings): string
    {
        $prefixType = $settings['prefix_type'] ?? 'default';

        return match ($prefixType) {
            'school' => strtoupper(substr(
                preg_replace(
                    '/[^a-zA-Z]/',
                    '',
                    $this->tenant->name
                ),
                0,
                3
            )),
            'custom' => $settings['prefix'] ?? 'EMP',
            default => 'EMP'
        };
    }

    /**
     * Get Department Prefix for ID
     * 
     * Order of precedence:
     * 1. Check custom prefix from settings
     * 2. Use first 3 letters of department name
     * 3. Fallback to 'GEN' if no department found
     * 
     * Example: "ADM" for Administration department
     */
    protected function getDepartmentPrefix(array $data): string
    {
        // Default to 'GEN' if no designation provided
        if (!isset($data['designation_id'])) {
            return 'GEN';
        }

        $designation = Designation::find($data['designation_id']);
        if (!$designation) {
            return 'GEN';
        }

        // Check for custom prefix in settings
        $departmentPrefixes = $this->settings->employee_id_department_prefixes;
        $designationId = (string) $data['designation_id'];

        if (isset($departmentPrefixes[$designationId])) {
            return $departmentPrefixes[$designationId];
        }

        // Use first 3 letters of department name
        return strtoupper(substr($designation->name, 0, 3));
    }

    /**
     * Parse format string and replace placeholders
     * 
     * Placeholders:
     * {PREFIX} - School's prefix (e.g., "EMP")
     * {YYYY}   - Full year (e.g., "2023")
     * {YY}     - Short year (e.g., "23")
     * {MM}     - Month (e.g., "01")
     * {DD}     - Day (e.g., "15")
     * {DEPT}   - Department code (e.g., "ADM")
     * {NUM:n}  - Sequential number with n digits (e.g., "0001")
     */

    /**
     * Get next number based on existing IDs using settings
     */
    protected function getNextNumber(string $format): string
    {
        // Get number length from settings
        $length = $this->settings->employee_settings['number_length'] ?? 3;

        // If there are no staff records, start from 1
        if ($this->staff->where('school_id', $this->tenant->id)->doesntExist()) {
            return str_pad('1', $length, '0', STR_PAD_LEFT);
        }

        // Get year for pattern matching
        $year = date('Y');
        $shortYear = date('y');

        // Build regex pattern to extract just the sequence number
        $pattern = '/\d{' . $length . '}$/'; // Match exactly $length digits at the end

        // Get last employee ID
        $lastStaff = $this->staff
            ->where('school_id', $this->tenant->id)
            ->orderBy('employee_id', 'desc')
            ->first();

        if (!$lastStaff) {
            return str_pad('1', $length, '0', STR_PAD_LEFT);
        }

        try {
            // Extract number from last ID
            if (preg_match($pattern, $lastStaff->employee_id, $matches)) {
                $lastNumber = (int) $matches[0];
                $nextNumber = $lastNumber + 1;
            } else {
                // If pattern doesn't match, start from 1
                $nextNumber = 1;
            }
        } catch (\Exception $e) {
            // If there's any regex error, fallback to 1
            $nextNumber = 1;
        }

        return str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Build search pattern for custom format
     */
    protected function buildCustomSearchPattern(string $format, int $length): string
    {
        try {
            // Replace all tokens with their regex equivalents, making year optional
            $pattern = preg_quote($format, '/');
            $prefix = preg_quote($this->settings->employee_settings['prefix'] ?? 'EMP', '/');

            $pattern = str_replace(
                [
                    preg_quote('{PREFIX}', '/'),
                    preg_quote('{YYYY}', '/'),
                    preg_quote('{YY}', '/'),
                    preg_quote('{MM}', '/'),
                    preg_quote('{DD}', '/'),
                    preg_quote('{DEPT}', '/'),
                    preg_quote("{NUM:$length}", '/'),
                ],
                [
                    $prefix,
                    '\d{4}',
                    '\d{2}',
                    '\d{2}',
                    '\d{2}',
                    '[A-Z]{3}',
                    "(\d{$length})",
                ],
                $pattern
            );

            return "/\d{$length}$/"; // Match only the sequence number at the end
        } catch (\Exception $e) {
            // Return a safe fallback pattern if there's an error
            return "/(\d+)$/";
        }
    }

    public function generate(array $data = []): string
    {
        try {
            // For admin staff during school setup, use special format
            if ($data['is_admin'] ?? false) {
                return $this->generateAdminId();
            }

            // Get the format based on settings or provided data
            $format = $this->getFormat($data);

            // Parse the format to create the ID
            $generatedId = $this->parseFormat($format, $data);

            // Ensure ID is unique
            return $this->ensureUnique($generatedId);
        } catch (\Exception $e) {
            Log::error('Employee ID generation error', [
                'error' => $e->getMessage(),
                'school_id' => $this->tenant->id ?? null
            ]);
            return $this->generateFallbackId();
        }
    }

    protected function generateAdminId(): string
    {
        // Get school initials
        $schoolInitials = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $this->tenant->name), 0, 3));

        // Get year
        $year = date('y');

        // Admin number is always 001
        $adminNumber = '001';

        // Format: KPS/23/001
        return "{$schoolInitials}/{$year}/{$adminNumber}";
    }

    protected function generateFallbackId(): string
    {
        $schoolInitials = $this->tenant ?
            strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $this->tenant->name), 0, 3)) :
            'EMP';

        return $schoolInitials . '/' . date('y') . '/' . str_pad('1', 3, '0', STR_PAD_LEFT);
    }

    protected function ensureUnique(string $id): string
    {
        while ($this->staff->where('school_id', $this->tenant->id)
            ->where('employee_id', $id)->exists()
        ) {
            // Extract current number and increment
            preg_match('/(\d+)$/', $id, $matches);

            // Make sure we have matches before trying to access them
            if (!empty($matches[1])) {
                $number = intval($matches[1]) + 1;
                $padLength = strlen($matches[1]);
                $id = preg_replace(
                    '/\d+$/',
                    str_pad($number, $padLength, '0', STR_PAD_LEFT),
                    $id
                );
            } else {
                // Fallback if no number found at the end
                $id .= '1';
            }
        }

        return $id;
    }

    /**
     * Create the base pattern for ID format
     */
    protected function createBasePattern(string $format, array $tokens): string
    {
        $pattern = preg_quote($format, '/');

        foreach ($tokens as $token => $replacement) {
            $pattern = str_replace(
                preg_quote($token),
                $replacement,
                $pattern
            );
        }

        return '/^' . $pattern . '$/';
    }

    public function generateWithOptions(array $options = []): string
    {
        try {
            $parts = [];

            if ($options['include_prefix'] ?? true) {
                $parts[] = $this->generateSchoolInitials(
                    ($options['prefix_type'] ?? 'consonants') === 'consonants'
                );
            }

            if ($options['include_year'] ?? true) {
                $parts[] = match ($options['year_format'] ?? 'short') {
                    'full' => date('Y'),
                    default => date('y')
                };
            }

            // Use provided sequence number or get next available
            $sequence = $options['sequence'] ?? $this->getNextAvailableSequence($options);
            $parts[] = str_pad((string)$sequence, $options['number_length'] ?? 3, '0', STR_PAD_LEFT);

            $separator = ($options['include_separator'] ?? true) ? ($options['separator'] ?? '/') : '';

            return implode($separator, $parts);
        } catch (\Exception $e) {
            Log::error('Employee ID generation error', [
                'error' => $e->getMessage(),
                'school_id' => $this->tenant->id ?? null
            ]);
            return $this->generateFallbackId();
        }
    }

    protected function getNextAvailableSequence(array $options = []): int
    {
        // Get the current prefix that will be used
        $prefix = '';
        if ($options['include_prefix'] ?? true) {
            $prefix = $this->generateSchoolInitials(
                ($options['prefix_type'] ?? 'consonants') === 'consonants'
            );
        }

        // Get the year part if included
        $yearPart = '';
        if ($options['include_year'] ?? true) {
            $yearToUse = !empty($options['custom_year']) ?
                date('Y', strtotime($options['custom_year'])) :
                date('Y');

            $yearPart = $options['year_format'] === 'full' ?
                $yearToUse :
                substr($yearToUse, -2);
        }

        // Build the search pattern based on current format
        $separator = ($options['include_separator'] ?? true) ? ($options['separator'] ?? '/') : '';

        // Escape special characters in prefix and separator for regex
        $prefixPattern = preg_quote($prefix, '/');
        $separatorPattern = preg_quote($separator, '/');
        $yearPattern = preg_quote($yearPart, '/');

        // Build the pattern to match IDs with the same prefix and year
        $pattern = '/';
        if ($prefix) {
            $pattern .= $prefixPattern . $separatorPattern;
        }
        if ($yearPart) {
            $pattern .= $yearPattern . $separatorPattern;
        }
        $pattern .= '(\d+)$/';

        // Get last ID matching this pattern
        $lastStaff = $this->staff
            ->where('school_id', $this->tenant->id)
            ->where(function ($query) use ($pattern) {
                $query->whereRaw("employee_id REGEXP ?", [$pattern]);
            })
            ->orderByRaw("CAST(REGEXP_REPLACE(employee_id, '^.*[^0-9]', '') AS UNSIGNED) DESC")
            ->first();

        if (!$lastStaff) {
            return 1;
        }

        // Extract the sequence number
        if (preg_match($pattern, $lastStaff->employee_id, $matches)) {
            return ((int) $matches[1]) + 1;
        }

        return 1;
    }

    public function regenerateAllIds(array $options = []): void
    {
        $staff = Staff::where('school_id', $this->tenant->id)
            ->orderBy('hire_date')
            ->get();

        DB::transaction(function () use ($staff) {
            $sequence = 1;
            $settings = $this->settings->employee_settings;

            foreach ($staff as $employee) {
                // Get year based on settings
                $yearToUse = !empty($settings['custom_year'])
                    ? substr($settings['custom_year'], 0, 4)  // Extract just the year part
                    : date('Y');

                if ($settings['year_format'] === 'short') {
                    $yearToUse = substr($yearToUse, -2);
                }

                $parts = [];

                // Add prefix if enabled
                if ($settings['include_prefix'] ?? true) {
                    $parts[] = $this->generateSchoolInitials(
                        ($settings['prefix_type'] ?? 'consonants') === 'consonants'
                    );
                }

                // Add year if enabled
                if ($settings['include_year'] ?? true) {
                    $parts[] = $yearToUse;
                }

                // Add sequence number
                $parts[] = str_pad((string)$sequence++, $settings['number_length'] ?? 3, '0', STR_PAD_LEFT);

                // Join with separator if enabled
                $separator = ($settings['include_separator'] ?? true) ? ($settings['separator'] ?? '/') : '';

                $newId = implode($separator, $parts);

                $employee->update(['employee_id' => $newId]);
            }
        });
    }

    protected function generateWithCustomYear(string $year, array $options = []): string
    {
        $schoolInitials = $this->generateSchoolInitials($options['use_consonants'] ?? true);
        $sequence = $options['sequence'] ?? $this->getNextAvailableSequence(['custom_year' => $year]);
        $settings = $this->settings->employee_settings;

        $parts = [];

        // Add prefix if enabled
        if ($settings['include_prefix'] ?? true) {
            $parts[] = $schoolInitials;
        }

        // Add year if enabled
        if ($settings['include_year'] ?? true) {
            $parts[] = $year;
        }

        // Add sequence number
        $parts[] = str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);

        // Join with separator if enabled
        $separator = ($settings['include_separator'] ?? true) ? ($settings['separator'] ?? '/') : '';

        return implode($separator, $parts);
    }

    public function previewFormat(array $options = []): string
    {
        $prefix = match ($options['prefix_type'] ?? 'consonants') {
            'consonants' => $this->generateSchoolInitials(true),
            'first_letters' => $this->generateSchoolInitials(false),
            default => $options['prefix'] ?? 'EMP'
        };

        $parts = [];

        if ($options['include_prefix'] ?? true) {
            $parts[] = $prefix;
        }

        if ($options['include_year'] ?? true) {
            // Use custom year if provided, otherwise current year
            $yearToUse = !empty($options['custom_year']) ?
                date('Y', strtotime($options['custom_year'])) :
                date('Y');

            $parts[] = match ($options['year_format'] ?? 'short') {
                'full' => $yearToUse,
                default => substr($yearToUse, -2)
            };
        }

        $parts[] = str_pad('1', $options['number_length'] ?? 3, '0', STR_PAD_LEFT);

        $separator = ($options['include_separator'] ?? true) ? ($options['separator'] ?? '/') : '';

        return implode($separator, $parts);
    }

    public function generateSchoolInitials(bool $useConsonants = true): string
    {
        $name = $this->tenant->name;

        if ($useConsonants) {
            // Get consonants from school name
            preg_match_all('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]/', $name, $matches);
            $consonants = $matches[0];
            $initials = array_slice($consonants, 0, 3);
            return strtoupper(implode('', $initials));
        } else {
            // Get first letter of each word
            $words = explode(' ', $name);
            $initials = array_map(fn($word) => substr($word, 0, 1), $words);
            return strtoupper(implode('', array_slice($initials, 0, 3)));
        }
    }

    public function getInitialSequence(array $options = []): int
    {
        // Get the current format components
        $prefix = '';
        if ($options['include_prefix'] ?? true) {
            $prefix = $this->generateSchoolInitials(
                ($options['prefix_type'] ?? 'consonants') === 'consonants'
            );
        }

        // Get year if included
        $yearPart = '';
        if ($options['include_year'] ?? true) {
            $yearToUse = !empty($options['custom_year']) ?
                date('Y', strtotime($options['custom_year'])) :
                date('Y');

            $yearPart = $options['year_format'] === 'full' ?
                $yearToUse :
                substr($yearToUse, -2);
        }

        // Build the pattern to match existing IDs
        $separator = ($options['include_separator'] ?? true) ? ($options['separator'] ?? '/') : '';

        $prefixPattern = preg_quote($prefix, '/');
        $separatorPattern = preg_quote($separator, '/');
        $yearPattern = preg_quote($yearPart, '/');

        $pattern = '/';
        if ($prefix) {
            $pattern .= $prefixPattern . $separatorPattern;
        }
        if ($yearPart) {
            $pattern .= $yearPattern . $separatorPattern;
        }
        $pattern .= '(\d+)$/';

        // Find last matching ID
        $lastStaff = $this->staff
            ->where('school_id', $this->tenant->id)
            ->where(function ($query) use ($pattern) {
                $query->whereRaw("employee_id REGEXP ?", [$pattern]);
            })
            ->orderByRaw("CAST(REGEXP_REPLACE(employee_id, '^.*[^0-9]', '') AS UNSIGNED) DESC")
            ->first();

        if (!$lastStaff) {
            return 1;
        }

        if (preg_match($pattern, $lastStaff->employee_id, $matches)) {
            return ((int) $matches[1]) + 1;
        }

        return 1;
    }

    // public function generateNextId(): string
    // {
    //     $settings = $this->settings->employee_settings;
    //     $parts = [];

    //     // 1. Get prefix based on settings
    //     if ($settings['include_prefix'] ?? true) {
    //         $prefix = $this->generateSchoolInitials(
    //             ($settings['prefix_type'] ?? 'consonants') === 'consonants'
    //         );
    //         $parts[] = $prefix;
    //     }

    //     // 2. Handle year part with proper formatting
    //     if ($settings['include_year'] ?? true) {
    //         // First check if there's a custom year specified
    //         $yearToUse = !empty($settings['custom_year']) 
    //         ? substr($settings['custom_year'], 0, 4)  // Extract just the year part
    //         : date('Y');

    //         // Apply year format based on settings
    //         $yearPart = match ($settings['year_format'] ?? 'short') {
    //             'full' => $yearToUse,                    // Will output like "2023"
    //             'short' => substr($yearToUse, -2),       // Will output like "23"
    //             default => substr($yearToUse, -2)        // Default to short format
    //         };

    //         $parts[] = $yearPart;
    //     }

    //     // 3. Get the next sequence number
    //     $numLength = $settings['number_length'] ?? 3;
    //     $lastStaff = $this->staff
    //         ->where('school_id', $this->tenant->id)
    //         ->orderBy('employee_id', 'desc')
    //         ->first();

    //     $nextNumber = 1;
    //     if ($lastStaff) {
    //         // Extract just the numeric sequence at the end
    //         if (preg_match('/\d{' . $numLength . '}$/', $lastStaff->employee_id, $matches)) {
    //             $nextNumber = intval($matches[0]) + 1;
    //         }
    //     }

    //     // Format the sequence number with proper padding
    //     $parts[] = str_pad((string)$nextNumber, $numLength, '0', STR_PAD_LEFT);

    //     // 4. Join all parts with the configured separator
    //     $separator = ($settings['include_separator'] ?? true) ? ($settings['separator'] ?? '/') : '';

    //     return implode($separator, $parts);
    // }

    public function generateNextId(array $options = []): string
    {
        $settings = $this->settings->employee_settings;
        $schoolName = Filament::getTenant()->name;

        // Determine year to use
        $year = $this->determineYear($settings);
        $yearFormat = $settings['year_format'] ?? 'short';
        $formattedYear = $yearFormat === 'short' ? substr($year, -2) : $year;

        // Generate prefix based on settings
        $prefix = $settings['prefix'] ?? $this->generateSchoolInitials($settings['prefix_type'] === 'consonants');

        // Build the pattern base for checking existing IDs
        $patternBase = '';
        if ($settings['include_prefix']) {
            $patternBase .= $prefix;
            if ($settings['include_separator']) {
                $patternBase .= $settings['separator'];
            }
        }
        if ($settings['include_year']) {
            $patternBase .= $formattedYear;
            if ($settings['include_separator']) {
                $patternBase .= $settings['separator'];
            }
        }

        // Find the highest number for this pattern
        $pattern = $patternBase . '%';
        $existingNumbers = Staff::where('employee_id', 'LIKE', $pattern)
            ->get()
            ->map(function ($staff) use ($patternBase) {
                $id = $staff->employee_id;
                $number = substr($id, strlen($patternBase));
                return (int) $number;
            });

        $nextNumber = $existingNumbers->isEmpty() ? 1 : ($existingNumbers->max() + 1);

        // Format the sequential number
        $numberLength = $settings['number_length'] ?? 3;
        $formattedNumber = str_pad($nextNumber, $numberLength, '0', STR_PAD_LEFT);

        // Build the final ID
        $parts = [];
        if ($settings['include_prefix']) {
            $parts[] = $prefix;
        }
        if ($settings['include_year']) {
            $parts[] = $formattedYear;
        }
        $parts[] = $formattedNumber;

        return implode($settings['include_separator'] ? $settings['separator'] : '', $parts);
    }

    protected function determineYear(array $settings): string
    {
        // If custom_year is set, extract just the year part without using date()
        if (!empty($settings['custom_year'])) {
            // Extract first 4 digits which should be the year
            if (preg_match('/^\d{4}/', $settings['custom_year'], $matches)) {
                return $matches[0];
            }
            // Fallback: take first 4 characters if they're all digits
            $year = substr($settings['custom_year'], 0, 4);
            if (ctype_digit($year)) {
                return $year;
            }
        }
        // Fallback to current year if no valid custom year
        return (string) now()->year;
    }
}
