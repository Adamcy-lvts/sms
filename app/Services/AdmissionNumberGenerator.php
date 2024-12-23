<?php

namespace App\Services;

use App\Models\Admission;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Log;
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
        $this->admission = app(Admission::class);
        $this->settings = $this->tenant->settings;
        // dd($this->settings);
        $this->separator = $this->settings->admission_settings['separator'] ?? '-';
        $this->format = $this->getFormatTemplate();
    }

    public function generate(): string
    {
        try {
            // Get base components
            $prefix = $this->settings->admission_settings['prefix'];
            $schoolInitials = $this->getSchoolInitials();
            $year = date('y');
            $session = $this->getSessionCode();
            $sequentialNumber = $this->getNextSequentialNumber();

            // Build replacements array
            $replacements = [
                '{PREFIX}' => $prefix ?? $schoolInitials,
                '{SCHOOL}' => $schoolInitials,
                '{YY}' => $year,
                '{YYYY}' => date('Y'),
                '{SESSION}' => $session,
                '{NUM}' => $sequentialNumber,
                '{SEP:1}' => $this->separator,
                '{SEP:2}' => $this->separator,
                '{SEP:3}' => $this->separator,
            ];

            // Replace all tokens in the format
            $admissionNumber = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $this->format
            );

            // Clean up separators and ensure uniqueness
            $admissionNumber = $this->cleanupSeparators($admissionNumber);
            return $this->ensureUnique($admissionNumber);
        } catch (\Exception $e) {
            Log::error('Admission number generation failed', [
                'error' => $e->getMessage(),
                'school_id' => $this->tenant->id,
                'format' => $this->format
            ]);
            return $this->generateFallbackNumber();
        }
    }

    public function previewFormat(string $format): string
    {
        try {
            $replacements = [
                '{PREFIX}' => $this->settings->admission_settings['prefix'] ?? 'ADM',
                '{SCHOOL}' => $this->getSchoolInitials(),
                '{YY}' => date('y'),
                '{YYYY}' => date('Y'),
                '{SESSION}' => $this->getSessionCode(),
                '{NUM}' => str_pad('1', $this->settings->admission_settings['length'] ?? 4, '0', STR_PAD_LEFT),
                '{SEP:1}' => $this->separator,
                '{SEP:2}' => $this->separator,
                '{SEP:3}' => $this->separator,
            ];

            return str_replace(
                array_keys($replacements),
                array_values($replacements),
                $format
            );
        } catch (\Exception $e) {
            return 'Invalid format';
        }
    }

    protected function getFormatTemplate(): string
    {
        // Get the format type and custom format from settings
        $formatType = $this->settings->admission_settings['format_type'];
        $customFormat = $this->settings->admission_settings['custom_format'] ?? [];

        // If using custom format and it exists, validate and use it
        if ($formatType === 'custom' && !empty($customFormat)) {
            if ($this->validateCustomFormat($customFormat)) {
                return $customFormat;
            }
            Log::warning('Invalid custom format, falling back to preset', [
                'custom_format' => $customFormat,
                'school_id' => $this->tenant->id
            ]);
        }

        // Fallback to preset format
        return $this->getPresetFormat($formatType ?? 'basic');
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
        // Use cached value if available
        if (isset($this->cache['school_initials'])) {
            return $this->cache['school_initials'];
        }

        // If custom initials are set, use them
        if (!empty($this->settings->admission_settings['school_initials'])) {
            $initials = $this->settings->admission_settings['school_initials'];
        } else {
            // Generate based on method
            $method = $this->settings->admission_settings['initials_method'] ?? 'first_letters';
            $initials = $this->generateInitials(
                $this->tenant->name,
                $method
            );
        }

        // Cache and return
        $this->cache['school_initials'] = strtoupper($initials);
        return $this->cache['school_initials'];
    }

    protected function generateInitials(string $name, string $method): string
    {
        return match ($method) {
            'first_letters' => $this->getFirstLettersInitials($name),
            'significant_words' => $this->getSignificantWordsInitials($name),
            'consonants' => $this->getFirstConsonants($name),
            default => $this->getFirstLettersInitials($name)
        };
    }

    protected function getFirstLettersInitials(string $name): string
    {
        $words = array_filter(explode(' ', $name));
        if (count($words) === 1) {
            return substr($words[0], 0, 3);
        }
        $initials = array_slice(array_map(fn($word) => substr($word, 0, 1), $words), 0, 3);
        return implode('', $initials);
    }

    protected function getSignificantWordsInitials(string $name): string
    {
        $skipWords = ['the', 'of', 'and', 'in', 'at', 'by', 'for'];
        $words = array_filter(
            explode(' ', strtolower($name)),
            fn($word) => !in_array($word, $skipWords)
        );
        $initials = array_map(fn($word) => substr($word, 0, 1), $words);
        return implode('', array_slice($initials, 0, 3));
    }

    protected function getFirstConsonants(string $name): string
    {
        $words = array_filter(explode(' ', $name));
        $consonants = '';

        foreach ($words as $word) {
            if (preg_match('/[bcdfghjklmnpqrstvwxyz]/i', $word, $matches)) {
                $consonants .= $matches[0];
            } else {
                $consonants .= substr($word, 0, 1);
            }
            if (strlen($consonants) >= 3) break;
        }

        return substr(str_pad($consonants, 3, 'X'), 0, 3);
    }

    protected function getSessionCode(): string
    {
        $session = $this->getCurrentSession();
        if (!$session) {
            return date('y');
        }

        $format = $this->settings->admission_settings['session_format'] ?? 'short_session';
        return $this->formatSessionString($session->name, $format);
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

        $session = AcademicSession::where('school_id', $this->tenant->id)
            ->where('is_current', true)
            ->first();

        $this->cache['current_session'] = $session;
        return $session;
    }

    protected function getNextSequentialNumber(): string
    {
        $length = $this->settings->admission_settings['length'] ?? 3;
        $query = $this->admission->where('school_id', $this->tenant->id);

        if ($this->settings->admission_settings['reset_sequence_yearly'] ?? false) {
            $query->whereYear('created_at', date('Y'));
        }

        if ($this->settings->admission_settings['reset_sequence_by_session'] ?? false) {
            $currentSession = $this->getCurrentSession();
            if ($currentSession) {
                $query->where('academic_session_id', $currentSession->id);
            }
        }

        $lastAdmission = $query->orderByDesc('admission_number')->first();

        if (!$lastAdmission) {
            $startNumber = $this->settings->admission_settings['number_start'] ?? 1;
            return str_pad($startNumber, $length, '0', STR_PAD_LEFT);
        }

        if (preg_match('/(\d+)$/', $lastAdmission->admission_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
            return str_pad($nextNumber, $length, '0', STR_PAD_LEFT);
        }

        return str_pad('1', $length, '0', STR_PAD_LEFT);
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

        throw new \RuntimeException("Could not generate unique admission number after $maxAttempts attempts");
    }

    protected function generateFallbackNumber(): string
    {
        $prefix = $this->getSchoolInitials();
        $year = date('y');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        return "{$prefix}{$this->separator}{$year}{$this->separator}{$random}";
    }
}
