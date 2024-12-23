<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Designation;
use App\Settings\AppSettings;
use Filament\Facades\Filament;

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

    protected function getFormat(array $data): string
    {
        // Check if specific format is requested
        if (isset($data['id_format'])) {
            return $this->getFormatByType($data['id_format'], $data);
        }

        // Get from settings
        return $this->getFormatByType(
            $this->settings->employee_id_format_type,
            $data
        );
    }

    protected function getFormatByType(string $type, array $data): string
    {
        if ($type === 'custom') {
            return $this->settings->employee_id_custom_format;
        }

        return match ($type) {
            'basic' => '{PREFIX}{NUM:' . $this->settings->employee_id_number_length . '}',
            'with_year' => '{PREFIX}{YY}{NUM:' . $this->settings->employee_id_number_length . '}',
            'with_department' => '{DEPT}{YY}{NUM:' . $this->settings->employee_id_number_length . '}',
            default => '{PREFIX}{YYYY}{NUM:' . $this->settings->employee_id_number_length . '}'
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
    protected function parseFormat(string $format, array $data): string
    {
         // Example replacements:
        // Input: "{PREFIX}{YY}{NUM:4}"
        // Step 1: "EMP{YY}{NUM:4}"
        // Step 2: "EMP23{NUM:4}"
        // Final: "EMP230001"
        // Get the actual number length from settings
        $numLength = $this->settings->employee_id_number_length;

        // First replace the NUM token with the actual length
        $format = str_replace('{NUM}', "{NUM:$numLength}", $format);

        $replacements = [
            '{PREFIX}' => $this->settings->employee_id_prefix,
            '{YYYY}' => date('Y'),
            '{YY}' => date('y'),
            '{MM}' => date('m'),
            '{DD}' => date('d'),
            '{DEPT}' => $this->getDepartmentPrefix($data),
            "{NUM:$numLength}" => $this->getNextNumber($format),
        ];

        $result = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $format
        );

        // Add separator if configured
        if ($this->settings->employee_id_separator) {
            $result = implode(
                $this->settings->employee_id_separator,
                str_split($result, 3)
            );
        }

        return $result;
    }

    /**
     * Get next number based on existing IDs using settings
     */
    protected function getNextNumber(string $format): string
    {
        // Use number length from settings
        $length = $this->settings->employee_id_number_length;

        // Create pattern for finding last number based on format type
        $searchPattern = match ($this->settings->employee_id_format_type) {
            'basic' => sprintf(
                '/^%s(\d{%d})$/',
                $this->settings->employee_id_prefix,
                $length
            ),
            'with_year' => sprintf(
                '/^%s\d{2}(\d{%d})$/',
                $this->settings->employee_id_prefix,
                $length
            ),
            'with_department' => sprintf(
                '/^[A-Z]{3}\d{2}(\d{%d})$/',
                $length
            ),
            'custom' => $this->buildCustomSearchPattern($format, $length),
            default => sprintf(
                '/^%s\d{4}(\d{%d})$/',
                $this->settings->employee_id_prefix,
                $length
            )
        };

        // Get last staff with matching pattern
        $lastStaff = $this->staff
            ->where('school_id', $this->tenant->id)
            ->where('employee_id', 'REGEXP', $searchPattern)
            ->orderBy('employee_id', 'desc')
            ->first();

        if (!$lastStaff) {
            return str_pad('1', $length, '0', STR_PAD_LEFT);
        }

        // Extract and increment last number
        if (preg_match($searchPattern, $lastStaff->employee_id, $matches)) {
            $lastNumber = intval($matches[1]) + 1;
            return str_pad($lastNumber, $length, '0', STR_PAD_LEFT);
        }

        // Fallback to starting number if pattern doesn't match
        return str_pad('1', $length, '0', STR_PAD_LEFT);
    }

    /**
     * Build search pattern for custom format
     */
    protected function buildCustomSearchPattern(string $format, int $length): string
    {
        // Replace all tokens with their regex equivalents
        $pattern = preg_quote($format, '/');
        $pattern = str_replace(
            [
                preg_quote('{PREFIX}'),
                preg_quote('{YYYY}'),
                preg_quote('{YY}'),
                preg_quote('{MM}'),
                preg_quote('{DD}'),
                preg_quote('{DEPT}'),
                preg_quote("{NUM:$length}"),
            ],
            [
                preg_quote($this->settings->employee_id_prefix),
                '\d{4}',
                '\d{2}',
                '\d{2}',
                '\d{2}',
                '[A-Z]{3}',
                "(\d{$length})",
            ],
            $pattern
        );

        return '/^' . $pattern . '$/';
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
