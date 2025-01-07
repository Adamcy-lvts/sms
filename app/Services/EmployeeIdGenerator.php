<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Designation;
use App\Settings\AppSettings;
use Filament\Facades\Filament;
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
        $this->settings = $settings ?? $this->tenant->getSettingsAttribute();
    }

    // In EmployeeIdGenerator.php and AdmissionNumberGenerator.php

    protected function getSettings()
    {
        if (!$this->tenant) {
            return null;
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
                preg_replace('/[^a-zA-Z]/', '', 
                $this->tenant->name), 
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

        // Create pattern to extract the numeric part based on format type
        $formatType = $this->settings->employee_settings['format_type'] ?? 'basic';
        $prefix = preg_quote($this->settings->employee_settings['prefix'] ?? 'EMP', '/');
        $separator = preg_quote($this->settings->employee_settings['separator'] ?? '', '/');

        // Build regex pattern based on format type with properly escaped characters
        $pattern = match ($formatType) {
            'basic' => "/^{$prefix}{$separator}(\d+)$/",
            'with_year' => "/^{$prefix}{$separator}\d{2}(\d{" . $length . "})$/",
            'with_department' => "/^[A-Z]{3}{$separator}\d{2}(\d{" . $length . "})$/",
            'custom' => $this->buildCustomSearchPattern($format, $length),
            default => "/^{$prefix}{$separator}(\d+)$/"
        };

        // Get last employee ID
        $lastStaff = $this->staff
            ->where('school_id', $this->tenant->id)
            ->orderBy('employee_id', 'desc')
            ->first();

        if (!$lastStaff) {
            return str_pad('1', $length, '0', STR_PAD_LEFT);
        }

        try {
            // Extract number from last ID with error handling
            if (preg_match($pattern, $lastStaff->employee_id, $matches)) {
                $lastNumber = (int) ($matches[1] ?? 0);
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
            // Replace all tokens with their regex equivalents
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

            return "/^$pattern$/";
        } catch (\Exception $e) {
            // Return a safe fallback pattern if there's an error
            return "/(\d+)$/";
        }
    }

    public function generate(array $data = []): string
    {
        try {
            // Get the format based on settings or provided data
            $format = $this->getFormat($data);

            // Parse the format to create the ID
            $generatedId = $this->parseFormat($format, $data);

            // Ensure ID is unique
            return $this->ensureUnique($generatedId);
        } catch (\Exception $e) {
            // Fallback to basic format if something goes wrong
            $basicFormat = '{PREFIX}{YY}{NUM:' . $this->settings->employee_id_number_length . '}';
            return $this->parseFormat($basicFormat, $data);
        }
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
}
