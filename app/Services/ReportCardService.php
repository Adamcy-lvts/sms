<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\School;
use App\Models\Student;
use App\Models\ReportCard;
use App\Models\AssessmentType;
use App\Models\ReportTemplate;
use App\Services\GradeService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

class ReportCardService
{
    protected $gradeService;
    public $template;
    protected $cache;
    protected const CACHE_TTL = 3600; // 1 hour cache lifetime

    public function __construct(GradeService $gradeService)
    {
        $this->gradeService = $gradeService;

        // Get current tenant (school)
        // $tenant = Filament::getTenant();
        $tenant = School::find(1);

        // Initialize cache with tenant-specific tag
        $this->cache = Cache::tags([
            "school:{$tenant->slug}",  // Tenant-specific tag
            'report-cards'             // Feature-specific tag
        ]);
    }

    /**
     * Generate a tenant-aware cache key for report cards
     */
    protected function generateCacheKey($studentId, int $termId, int $academicSessionId, ?string $templateId = null): string
    {

        // Build cache key components
        return implode(':', [
            'report',
            $studentId,
            $termId,
            $academicSessionId,
            $templateId ?? 'default'
        ]);
    }

    public function generateReport(Student $student, int $termId, int $academicSessionId, ?string $templateId = null): array
    {
        // Generate cache key
        $cacheKey = $this->generateCacheKey($student->id, $termId, $academicSessionId, $templateId);

        // Try to get from cache first
        $reportData = $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($student, $termId, $academicSessionId, $templateId) {
            $school = Filament::getTenant();
            $template = $this->getTemplate($school, $templateId);
            $this->template = $template;

            // Get assessment types
            $assessmentTypes = AssessmentType::where('school_id', $school->id)
                ->orderBy('id')
                ->get()
                ->toArray();

            // Get report data
            $reportData = $this->gradeService->generateTermReport(
                $student,
                $termId,
                $academicSessionId,
                $assessmentTypes
            );

            // Format report data
            $formattedData = $this->formatReportData($reportData, $template, $assessmentTypes);
            
            // Store the report card
            $this->storeReportCard($student, $termId, $academicSessionId, $templateId, $formattedData);

            return $formattedData;
        });

        return $reportData;
    }

    protected function storeReportCard(Student $student, int $termId, int $academicSessionId, ?string $templateId, array $formattedData): void 
    {
        try {
            $summary = $formattedData['term_summary'];
            $attendance = $formattedData['attendance'];

            ReportCard::firstOrCreate(
                [
                    'school_id' => $student->school_id,
                    'student_id' => $student->id,
                    'class_room_id' => $student->class_room_id,
                    'academic_session_id' => $academicSessionId,
                    'term_id' => $termId,
                    'template_id' => $templateId,
                ],
                [
                    'class_size' => $summary['class_size'],
                    'position' => $summary['position'],
                    'average_score' => $summary['average'],
                    'total_subjects' => $summary['total_subjects'],
                    'total_score' => $summary['total_score'],
                    'subject_scores' => $formattedData['subjects'],
                    'attendance_percentage' => $attendance['attendance_rate'] ?? 0,
                    'monthly_attendance' => $attendance['monthly_breakdown'] ?? [],
                    'status' => 'final',
                    'created_by' => auth()->id() ?? null,
                ]
            );

        } catch (\Exception $e) {
            Log::error('Failed to store report card', [
                'error' => $e->getMessage(),
                'student_id' => $student->id,
                'term_id' => $termId,
                'session_id' => $academicSessionId
            ]);
            // Don't throw the exception - just log it
            // We don't want to break report generation if storage fails
        }
    }

    /**
     * Invalidate cache for specific report or all tenant reports
     */
    public function invalidateCache(Student $student, ?int $termId = null, ?int $academicSessionId = null): void
    {
        $tenant = Filament::getTenant();

        if ($termId && $academicSessionId) {
            // Invalidate specific report
            $cacheKey = $this->generateCacheKey($student, $termId, $academicSessionId);
            $this->cache->forget($cacheKey);

            Log::info('Invalidated specific report cache', [
                'student' => $student->admission_number,
                'term' => $termId,
                'session' => $academicSessionId
            ]);
        } else {
            // Invalidate all reports for this tenant
            Cache::tags("school:{$tenant->slug}")->flush();

            Log::info('Invalidated all report caches for school', [
                'school' => $tenant->slug
            ]);
        }
    }

    protected function getTemplate(School $school, ?string $templateId): ReportTemplate
    {
        if ($templateId) {
            return ReportTemplate::where('school_id', $school->id)
                ->where('id', $templateId)
                ->firstOrFail();
        }

        return ReportTemplate::where('school_id', $school->id)
            ->where('is_default', true)
            ->firstOrFail();
    }


    protected function formatReportData(array $reportData, ReportTemplate $template, array $assessmentTypes): array
    {
        $formattedData = [
            'basic_info' => $this->formatBasicInfo($reportData['student'], $template, $reportData['summary']),
            'academic_info' => $this->formatAcademicInfo($reportData['academic_info']),
            'attendance' => $reportData['attendance'] ?? [],
            'subjects' => $this->formatSubjects($reportData['subjects'], $template, $assessmentTypes),
            'term_summary' => $this->formatSummary(
                $reportData['summary'],
                $reportData['attendance'] ?? [],
                $reportData['academic_info']['session']['id'] ?? null // Pass the academic session ID here
            ),
            'comments' => $reportData['comments'] ?? [],
            'template' => $template,
            'generated_at' => now(),
        ];

        // Add behavioral assessments if configured
        if (isset($reportData['behavioral_traits'])) {
            $formattedData['behavioral_traits'] = $this->formatBehavioralTraits(
                $reportData['behavioral_traits']
            );
        }

        // Add extra-curricular activities if configured
        if (isset($reportData['activities'])) {
            $formattedData['activities'] = $this->formatActivities(
                $reportData['activities']
            );
        }

        return $formattedData;
    }


    protected function getFieldValue($admission, $student, $columnName, array $termSummaryData = [])
    {
        // Handle term summary fields
        // if (in_array($columnName, array_keys(\App\Enums\TermSummaryFields::FIELDS))) {
        //     return $termSummaryData[$columnName] ?? null;
        // }

        if (in_array($columnName, array_keys(\App\Enums\TermSummaryFields::FIELDS))) {
            return data_get($termSummaryData, $columnName);
        }


        // Handle nested relationships
        if (str_contains($columnName, '.')) {
            $parts = explode('.', $columnName);
            $value = $student;
            foreach ($parts as $part) {
                $value = $value?->{$part};
            }
            return $value;
        }

        // Map special fields  
        $specialFields = [
            'class_room_id' => $student->classRoom?->name,
            'state_id' => $student->admission?->state?->name,
            'lga_id' => $student->admission?->lga?->name,
        ];

        return $specialFields[$columnName] ?? $admission->{$columnName} ?? null;
    }


    protected function formatBasicInfo(array $studentData, ReportTemplate $template, $termSummaryData): array
    {
        $admission = $studentData['admission'] ?? null;
        if (!$admission) {
            return $studentData;
        }

        // dd($studentData['id']);
        $student = Student::find($studentData['id']);

        $sections = $template->student_info_config['sections'] ?? [];


        return collect($sections)
            ->flatMap(function ($section) use ($admission, $student, $termSummaryData) {
                return collect($section['fields'] ?? [])
                    ->filter(fn($field) => $field['enabled'] ?? true)
                    ->mapWithKeys(function ($field) use ($admission, $student, $termSummaryData) {
                        $columnName = $field['admission_column'] ?? null;
                        // $value = $columnName ? $this->getFieldValue($admission, $student, $columnName) : null;
                        $value = $columnName ? $this->getFieldValue($admission, $student, $columnName, $termSummaryData) : null;

                        if ($value && $columnName === 'date_of_birth') {
                            // $value = Carbon::parse($value)->format('d/m/Y');
                            $value = Carbon::parse($value)->format('jS F, Y'); // e.g. "15th January, 2010"
                        }

                        return [$field['key'] => $value ?? '-'];
                    });
            })
            ->toArray();
    }

    protected function formatAcademicInfo(array $academicInfo): array
    {
        return [
            'session' => [
                'id' => $academicInfo['session']['id'],
                'name' => $academicInfo['session']['name'],
            ],
            'term' => [
                'id' => $academicInfo['term']['id'],
                'name' => $academicInfo['term']['name'],
            ],
        ];
    }

    protected function formatSubjects(array $subjects, ReportTemplate $template, array $assessmentTypes = null): array
    {
        $config = $template->getGradeTableConfig();

        // Get assessment types only if not provided
        if ($assessmentTypes === null) {
            $assessmentTypes = AssessmentType::where('school_id', Filament::getTenant()->id)
                ->orderBy('id')
                ->get()
                ->toArray();
        }

        // Build default columns once
        $defaultColumns = collect($assessmentTypes)->mapWithKeys(function ($type) {
            return [strtolower($type['code']) => '-'];
        })->toArray();

        return collect($subjects)->map(function ($subject) use ($config, $defaultColumns) {
            $formattedSubject = [
                'name' => $subject['name'],
                'name_ar' => $subject['name_ar'],
                'assessment_columns' => $subject['assessment_columns'] ?? $defaultColumns,
                'total' => $subject['total'],
                'grade' => $subject['grade'],
                'remark' => $subject['remark'],
            ];

            if ($config['show_position'] ?? false) {
                $formattedSubject['position'] = $subject['position'] ?? null;
            }

            return $formattedSubject;
        })->toArray();
    }

    protected function formatAssessmentScores(array $scores, array $configColumns): array
    {
        return collect($configColumns)
            ->mapWithKeys(function ($column) use ($scores) {
                $key = $column['key'];
                return [$key => $scores[$key] ?? null];
            })
            ->toArray();
    }

    protected function formatSummary(array $summary, array $attendance = [], ?int $academicSessionId = null): array
    {
        try {
            // Use the academicSessionId passed from the generateReport method
            $nextTerm = Term::where('academic_session_id', $academicSessionId)
                ->where('start_date', '>', now())
                ->orderBy('start_date', 'asc')
                ->first();

            // If no next term in current session, try to get first term of next session
            if (!$nextTerm) {
                $nextTerm = Term::whereHas('academicSession', function ($query) {
                    $query->where('start_date', '>', now());
                })
                    ->orderBy('start_date', 'asc')
                    ->first();
            }

            // Format the summary with null checks
            return [
                'total_subjects' => $summary['total_subjects'] ?? 0,
                'total_score' => $summary['total_score'] ?? 0,
                'average' => $summary['average'] ?? 0,
                'grade' => $summary['grade'] ?? 'N/A',
                'position' => $summary['position'] ?? 'N/A',
                'class_size' => $summary['class_size'] ?? 0,
                'class_average' => $summary['class_stats']['class_average'] ?? 0,
                'highest_average' => $summary['class_stats']['highest_average'] ?? 0,
                'lowest_average' => $summary['class_stats']['lowest_average'] ?? 0,
                // Add attendance info with null checks
                'attendance_percentage' => isset($attendance['attendance_rate'])
                    ? round($attendance['attendance_rate']) . '%'
                    : '0%',
                'school_days' => $attendance['school_days'] ?? 0,
                'days_present' => $attendance['present'] ?? 0,
                'days_absent' => $attendance['absent'] ?? 0,
                // Add resumption date with null check
                'resumption_date' => $nextTerm && $nextTerm->start_date
                    ? Carbon::parse($nextTerm->start_date)->format('jS F, Y')
                    : null
            ];
        } catch (\Exception $e) {
            Log::error('Error in formatSummary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'summary' => $summary,
                'attendance' => $attendance
            ]);

            // Return a safe default structure if an error occurs
            return [
                'total_subjects' => 0,
                'total_score' => 0,
                'average' => 0,
                'grade' => 'N/A',
                'position' => 'N/A',
                'class_size' => 0,
                'class_average' => 0,
                'highest_average' => 0,
                'lowest_average' => 0,
                'attendance_percentage' => '0%',
                'school_days' => 0,
                'days_present' => 0,
                'days_absent' => 0,
                'resumption_date' => null
            ];
        }
    }

    protected function formatComments(array $comments): array
    {
        return [
            'class_teacher' => [
                'comment' => $comments['class_teacher'] ?? null,
                'name' => $comments['class_teacher_name'] ?? null,
                'signature' => $comments['class_teacher_signature'] ?? null,
            ],
            'principal' => [
                'comment' => $comments['principal'] ?? null,
                'name' => $comments['principal_name'] ?? null,
                'signature' => $comments['principal_signature'] ?? null,
            ],
        ];
    }

    protected function formatBehavioralTraits(array $traits): array
    {
        return collect($traits)->map(function ($trait) {
            return [
                'name' => $trait['name'],
                'rating' => $trait['rating'],
                'remark' => $trait['remark'] ?? null,
            ];
        })->toArray();
    }

    protected function formatActivities(array $activities): array
    {
        // Group activities by section
        $groupedActivities = [];
        foreach ($activities as $activity) {
            $section = $activity['section'] ?? 'General';
            if (!isset($groupedActivities[$section])) {
                $groupedActivities[$section] = [
                    'title' => $section,
                    'enabled' => true,
                    'style' => ['background' => 'light'],
                    'fields' => []
                ];
            }

            $groupedActivities[$section]['fields'][] = [
                'name' => $activity['name'],
                'type' => 'rating',
                'enabled' => true,
                'value' => [
                    'rating' => $activity['value']['rating'] ?? 0,
                    'performance' => $activity['value']['performance'] ?? 'N/A'
                ],
                'style' => [
                    'text_color' => 'warning',
                    'alignment' => 'center'
                ]
            ];
        }

        return array_values($groupedActivities);
    }

    public function renderReport(array $reportData): string
    {
        return View::make('report-cards.show', [
            'data' => $reportData,
            'template' => $reportData['template'],
            'school' => Filament::getTenant(),
        ])->render();
    }
}
