<?php

namespace App\Services;

use App\Models\Status;
use App\Models\Admission;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\AdmissionNumberFormats;

class AdmissionNumberGenerator
{
    protected $tenant;
    protected $admission;
    protected $settings;
    protected $format;
    protected $separator;
    protected $cache = [];

    public function __construct()
    {
        $this->tenant = Filament::getTenant();
        if (!$this->tenant) {
            $this->settings = (object)['admission_settings' => []];
            return;
        }

        $this->admission = app(Admission::class);
        $this->settings = $this->tenant->settings ?? (object)['admission_settings' => []];
        $this->separator = $this->settings->admission_settings['separator'] ?? '-';
        $this->format = $this->getFormatTemplate();
    }

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

    public function generate(): string
    {
        if (!$this->tenant) {
            return '';
        }

        try {
            $settings = $this->settings->admission_settings ?? [];

            // Build the options array exactly like we do for preview
            $options = [
                'format_type' => $settings['format_type'] ?? 'basic',
                'custom_format' => $settings['custom_format'] ?? null,
                'school_prefix' => $settings['school_prefix'] ?? null,
                'separator' => $settings['separator'] ?? '-',
                'length' => $settings['length'] ?? 4,
                'include_session' => $settings['include_session'] ?? true,
                'custom_session' => $settings['custom_session'] ?? null,
                'session_format' => $settings['session_format'] ?? 'short_session',
                'initials_method' => $settings['initials_method'] ?? 'first_letters',
            ];

            $parts = [];

            // Only add prefix if include_prefix is true
            if ($settings['include_prefix'] ?? true) {
                $prefix = match ($settings['initials_method']) {
                    'consonants' => $this->generateSchoolInitials(true),
                    'first_letters' => $this->generateSchoolInitials(false),
                    default => $settings['school_prefix'] ?? 'ADM'
                };
                $parts[] = $prefix;
            }

            // Add session using same logic as preview
            if ($options['include_session']) {
                $sessionName = $this->getSessionForGenerate($options);
                $parts[] = $this->formatSessionString($sessionName, $options['session_format']);
            }

            // Add sequential number
            $parts[] = str_pad(
                (string) $this->getNextSequentialNumber(),
                $options['length'],
                '0',
                STR_PAD_LEFT
            );

            // Use separator only if enabled
            $separator = ($settings['include_separator'] ?? true) ? ($settings['separator'] ?? '/') : '';
            $number = implode($separator, $parts);
            return $this->ensureUnique($number);
        } catch (\Exception $e) {
            Log::error('Admission number generation failed', [
                'error' => $e->getMessage(),
                'school_id' => $this->tenant->id ?? 0,
            ]);
            return $this->generateFallbackNumber();
        }
    }

    public function previewFormat(array $options = []): string
    {
        try {
            $parts = [];
            
            // Only add prefix if include_prefix is true
            if ($options['include_prefix'] ?? true) {
                $useConsonants = $options['initials_method'] === 'consonants';
                $prefix = $options['school_prefix'] ?? $this->generateSchoolInitials($useConsonants);
                $parts[] = $prefix;
            }

            // Add session if enabled
            if ($options['include_session'] ?? true) {
                $sessionName = $this->getSessionForPreview($options);
                $parts[] = $this->formatSessionString($sessionName, $options['session_format'] ?? 'short_session');
            }

            // Add sequential number
            $parts[] = str_pad('1', $options['length'] ?? 4, '0', STR_PAD_LEFT);

            // Only use separator if include_separator is true
            $separator = ($options['include_separator'] ?? true) ? ($options['separator'] ?? '/') : '';
            
            return implode($separator, $parts);
        } catch (\Exception $e) {
            return 'Preview not available';
        }
    }

    protected function getSessionForGenerate(array $options): string
    {
        // First try custom session from settings
        if (!empty($options['custom_session'])) {
            return $options['custom_session'];
        }

        // Then try to get current session from database
        $currentSession = $this->getCurrentSession();
        if ($currentSession) {
            return $currentSession->name;
        }

        // Then try config
        if ($configSession = config('app.current_session')) {
            return $configSession->name;
        }

        // Fallback to current year
        $year = date('Y');
        return "{$year}/" . ($year + 1);
    }

    protected function getSessionForPreview(array $options): string
    {
        // First try custom session from options
        if (!empty($options['custom_session'])) {
            return $options['custom_session'];
        }

        // Then try to get current session from database
        $currentSession = AcademicSession::where('school_id', $this->tenant->id)
            ->where('is_current', true)
            ->first();
        
        if ($currentSession) {
            return $currentSession->name;
        }

        // Then try config
        if ($configSession = config('app.current_session')) {
            return $configSession->name;
        }

        // Fallback to current year
        $year = date('Y');
        return "{$year}/" . ($year + 1);
    }

    protected function getPreviewSession(string $format, string $session = '2023/2024'): string
    {
        $years = array_map('trim', explode('/', $session));
        
        return match ($format) {
            'short' => substr($years[0], -2),
            'short_session' => substr($years[0], -2) . substr($years[1], -2),
            'full_year' => $years[0],
            'full_session' => $session,
            default => substr($years[0], -2) . substr($years[1], -2)
        };
    }

    protected function getFormatTemplate(): string
    {
        $formatType = $this->settings->admission_settings['format_type'] ?? 'basic';
        $customFormat = $this->settings->admission_settings['custom_format'] ?? '';

        if ($formatType === 'custom' && $customFormat) {
            return $this->validateCustomFormat($customFormat) ?? $this->getPresetFormat('basic');
        }

        return $this->getPresetFormat($formatType);
    }

    public function validateCustomFormat(string $format): ?string
    {
        // Check for required components
        if (!str_contains($format, '{NUM}')) {
            return null;
        }

        // Get valid tokens
        $validTokens = array_keys(AdmissionNumberFormats::getAvailableTokens());

        // Extract all tokens from the format
        preg_match_all('/\{([^}]+)\}/', $format, $matches);

        // Validate each token
        foreach ($matches[0] as $token) {
            if (!in_array($token, $validTokens)) {
                return null;
            }
        }

        return $format;
    }

    protected function getPresetFormat(string $formatType): string
    {
        return match ($formatType) {
            'basic' => '{PREFIX}{SEP:1}{NUM}',
            'with_year' => '{PREFIX}{SEP:1}{YY}{SEP:2}{NUM}',
            'school_initials' => '{SCHOOL}{SEP:1}{NUM}',
            'school_year' => '{SCHOOL}{SEP:1}{YY}{SEP:2}{NUM}',
            'with_session' => '{PREFIX}{SEP:1}{SESSION}{SEP:2}{NUM}',
            'school_session' => '{SCHOOL}{SEP:1}{SESSION}{SEP:2}{NUM}',
            default => '{PREFIX}{SEP:1}{NUM}'
        };
    }

    protected function getSchoolInitials(): string
    {
        try {
            if (isset($this->cache['school_initials'])) {
                return $this->cache['school_initials'];
            }

            $settings = $this->settings->admission_settings ?? [];
            
            // Use the same generateSchoolInitials method as EmployeeIdGenerator
            $useConsonants = $settings['initials_method'] === 'consonants';
            $initials = $this->generateSchoolInitials($useConsonants);

            $this->cache['school_initials'] = strtoupper($initials);
            return $this->cache['school_initials'];
        } catch (\Exception $e) {
            Log::error('Error generating school initials: ' . $e->getMessage());
            return 'SCH'; // Fallback initials
        }
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

    protected function getSessionCode(): string
    {
        $settings = $this->settings->admission_settings;
        
        // Check if session should be included
        if (!($settings['include_session'] ?? true)) {
            return '';
        }

        // Use custom session if set
        if (!empty($settings['custom_session'])) {
            return $this->formatSessionString(
                $settings['custom_session'],
                $settings['session_format'] ?? 'short_session'
            );
        }

        // Fallback to current session
        $session = $this->getCurrentSession();
        if (!$session) {
            return date('y');
        }

        return $this->formatSessionString(
            $session->name,
            $settings['session_format'] ?? 'short_session'
        );
    }

    protected function formatSessionString(string $sessionName, string $format): string
    {
        if (!str_contains($sessionName, '/')) {
            return $sessionName;
        }

        $years = array_map('trim', explode('/', $sessionName));

        return match ($format) {
            'short' => substr($years[0], -2),
            'short_session' => substr($years[0], -2) . substr($years[1], -2),
            'full_year' => $years[0],
            'full_session' => $years[0] . '/' . $years[1],
            'custom' => $this->formatCustomSession($sessionName),
            default => substr($years[0], -2) . substr($years[1], -2)
        };
    }

    protected function formatCustomSession(string $sessionName): string
    {
        $format = $this->settings->admission_settings['session_custom_format'] ?? 'YYYY/YYYY+1';

        if (!str_contains($sessionName, '/')) {
            return $sessionName;
        }

        $years = array_map('trim', explode('/', $sessionName));
        if (count($years) !== 2) {
            return $sessionName;
        }

        $replacements = [
            'YYYY' => $years[0],
            'YYYY+1' => $years[1],
            'YY' => substr($years[0], -2),
            'YY+1' => substr($years[1], -2),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $format
        );
    }

    protected function getCurrentSession(): ?AcademicSession
    {
        if (isset($this->cache['current_session'])) {
            return $this->cache['current_session'];
        }

        $this->cache['current_session'] = AcademicSession::where('school_id', $this->tenant->id ?? 0)
            ->where('is_current', true)
            ->first();

        return $this->cache['current_session'];
    }

    protected function getNextSequentialNumber(): string
    {
        $settings = $this->settings->admission_settings ?? [];
        $length = $settings['length'] ?? 3;

        if (!$this->tenant) {
            Log::info('No tenant found, returning default sequence');
            return str_pad('1', $length, '0', STR_PAD_LEFT);
        }

        try {
            $query = $this->admission->where('school_id', $this->tenant->id);

            // Log initial query state
            Log::info('Initial query for school', [
                'school_id' => $this->tenant->id,
                'reset_by_session' => $settings['reset_sequence_by_session'] ?? true
            ]);

            // Apply session-based filtering only if reset_sequence_by_session is true
            if ($settings['reset_sequence_by_session'] ?? true) {
                $currentSession = null;
                $sessionValue = null;

                if (!empty($settings['custom_session'])) {
                    $currentSession = $settings['custom_session'];
                    Log::info('Using custom session', ['session' => $currentSession]);
                } else {
                    $currentSession = $this->getCurrentSession()?->name;
                    Log::info('Using current session', ['session' => $currentSession]);
                }

                if ($currentSession) {
                    // Format the session according to settings
                    $formattedSession = $this->formatSessionString(
                        $currentSession,
                        $settings['session_format'] ?? 'short_session'
                    );

                    Log::info('Formatted session for filtering', [
                        'original' => $currentSession,
                        'formatted' => $formattedSession,
                        'format' => $settings['session_format']
                    ]);

                    // Build pattern to match formatted session in admission numbers
                    $pattern = $formattedSession;
                    if ($settings['include_prefix'] ?? true) {
                        $prefix = $settings['school_prefix'] ?? $this->generateSchoolInitials($settings['initials_method'] === 'consonants');
                        $separator = ($settings['include_separator'] ?? true) ? ($settings['separator'] ?? '/') : '';
                        $pattern = $prefix . $separator . $formattedSession;
                    }

                    Log::info('Filtering by session pattern', ['pattern' => $pattern]);
                    
                    // Use LIKE query to match the formatted session pattern
                    $query->where('admission_number', 'LIKE', "%{$pattern}%");
                }
            }

            // Get the highest sequence number from matching records
            $lastNumber = $query->get()
                ->map(function($admission) use ($length) {
                    if (preg_match('/(\d{' . $length . '})$/', $admission->admission_number, $matches)) {
                        return (int)$matches[1];
                    }
                    return 0;
                })
                ->max();

            Log::info('Found highest sequence number', [
                'last_sequence' => $lastNumber ?? 'none',
                'query_sql' => $query->toSql(),
                'query_bindings' => $query->getBindings()
            ]);

            $startNumber = $settings['number_start'] ?? 1;
            $nextNumber = $lastNumber ? ($lastNumber + 1) : $startNumber;

            Log::info('Generating next sequence', [
                'next_sequence' => $nextNumber,
                'length' => $length
            ]);

            return str_pad($nextNumber, $length, '0', STR_PAD_LEFT);

        } catch (\Exception $e) {
            Log::error('Error generating sequence number', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return str_pad($settings['number_start'] ?? 1, $length, '0', STR_PAD_LEFT);
        }
    }

    protected function cleanupSeparators(string $number): string
    {
        if (!$this->separator) {
            return $number;
        }

        $number = preg_replace('/' . preg_quote($this->separator, '/') . '{2,}/', $this->separator, $number);
        return trim($number, $this->separator);
    }

    protected function ensureUnique(string $number): string
    {
        if (empty($number) || !$this->tenant) {
            return str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        }

        $originalNumber = $number;
        $counter = 1;
        $maxAttempts = 100;

        while ($counter < $maxAttempts) {
            $exists = $this->admission
                ->where('school_id', $this->tenant->id)
                ->where('admission_number', $number)
                ->exists();

            if (!$exists) {
                return $number;
            }

            if (preg_match('/^(.*?)(\d+)$/', $originalNumber, $matches)) {
                $base = $matches[1];
                $numLength = strlen($matches[2]);
                $nextNum = str_pad($counter, $numLength, '0', STR_PAD_LEFT);
                $number = $base . $nextNum;
            } else {
                $number = $originalNumber . $counter;
            }

            $counter++;
        }

        return $originalNumber . mt_rand(1000, 9999);
    }

    protected function generateFallbackNumber(): string
    {
        $prefix = $this->getSchoolInitials();
        $year = date('y');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        return "{$prefix}{$this->separator}{$year}{$this->separator}{$random}";
    }

    public function regenerateAllIds(array $options = []): void
    {
        $admissions = Admission::where('school_id', $this->tenant->id)
            ->orderBy('application_date')
            ->get();

        DB::transaction(function () use ($admissions, $options) {
            $sequence = 1;
            $settings = $this->settings->admission_settings;

            foreach ($admissions as $admission) {
                $parts = [];

                // Add prefix if enabled
                if ($settings['include_prefix'] ?? true) {
                    $prefix = $this->generateSchoolInitials(
                        ($settings['initials_method'] ?? 'first_letters') === 'consonants'
                    );
                    $parts[] = $prefix;
                }

                // Add session if enabled
                if ($settings['include_session'] ?? true) {
                    $sessionName = $admission->academicSession?->name ?? $this->getSessionForGenerate($options);
                    $parts[] = $this->formatSessionString($sessionName, $settings['session_format'] ?? 'short_session');
                }

                // Add sequence number
                $parts[] = str_pad(
                    (string) $sequence++,
                    $settings['length'] ?? 4,
                    '0',
                    STR_PAD_LEFT
                );

                // Join with separator if enabled
                $separator = ($settings['include_separator'] ?? true) ? ($settings['separator'] ?? '/') : '';
                $newNumber = implode($separator, $parts);

                $admission->update(['admission_number' => $newNumber]);
            }
        });
    }
}
