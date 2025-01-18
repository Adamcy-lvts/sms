<?php

namespace App\Filament\Sms\Pages;

use App\Models\Student;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Models\StudentGrade;
use App\Services\GradeService;
use Filament\Facades\Filament;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Services\PdfScalingService;
use App\Services\ReportCardService;
use Filament\Forms\Components\Grid;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Sms\Actions\BulkDownloadReportCards;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class TermReports extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static string $view = 'filament.sms.pages.term-reports';
    protected static ?string $title = 'Term Reports';
    protected static ?int $navigationSort = 7;

    public ?array $data = [];
    public ?array $report = null;
    public $school;
    private GradeService $gradeService;
    private ReportCardService $reportCardService;
    public $logoData;

    public function boot(GradeService $gradeService, ReportCardService $reportCardService)
    {
        $this->gradeService = $gradeService;
        $this->reportCardService = $reportCardService;
    }

    public function mount(): void
    {
        $this->school = Filament::getTenant();
        $this->form->fill();

        // Prepare logo data
        $this->logoData = null;
        if ($this->school->logo) {
            $logoPath = str_replace('public/', '', $this->school->logo);

            if (Storage::disk('public')->exists($logoPath)) {
                $fullLogoPath = Storage::disk('public')->path($logoPath);
                $extension = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
                $this->logoData = 'data:image/' . $extension . ';base64,' . base64_encode(
                    Storage::disk('public')->get($logoPath)
                );
            }
        }
    }


    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');

        return $form->schema([
            Section::make('Generate Term Report')
                ->description('Select class and student to generate report')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('class_room_id')
                            ->label('Class')
                            ->options(function () use ($tenant) {
                                return $tenant->classRooms()
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->live()
                            ->required(),

                        Select::make('student_id')
                            ->label('Student')
                            ->options(function (callable $get) use ($tenant) {
                                $classId = $get('class_room_id');
                                if (!$classId) return [];

                                return Student::query()
                                    ->where('school_id', $tenant->id)
                                    ->where('class_room_id', $classId)
                                    ->get()
                                    ->mapWithKeys(fn($student) => [
                                        $student->id => $student->full_name
                                    ]);
                            })
                            ->searchable()
                            ->live()
                            ->disabled(fn(callable $get) => !$get('class_room_id'))
                            ->required(),

                        Select::make('academic_session_id')
                            ->label('Academic Session')
                            ->native(false)
                            ->options(function () use ($tenant) {
                                return $tenant->academicSessions()
                                    ->orderByDesc('start_date')
                                    ->pluck('name', 'id');
                            })
                            ->default(fn() => $currentSession?->id)
                            ->live()
                            ->required(),

                        Select::make('term_id')
                            ->label('Term')
                            ->native(false)
                            ->options(function (callable $get) use ($tenant) {
                                $sessionId = $get('academic_session_id');
                                if (!$sessionId) return [];

                                return $tenant->terms()
                                    ->where('academic_session_id', $sessionId)
                                    ->orderBy('start_date')
                                    ->pluck('name', 'id');
                            })
                            ->default(function (callable $get) use ($currentSession, $currentTerm) {
                                return $get('academic_session_id') === $currentSession?->id
                                    ? $currentTerm?->id
                                    : null;
                            })
                            ->disabled(fn(callable $get) => !$get('academic_session_id'))
                            ->required(),

                        Select::make('template_id')
                            ->label('Report Template')
                            ->options(function () use ($tenant) {
                                return $tenant->reportTemplates()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('Use default template'),
                    ]),
                ]),
        ])->statePath('data');
    }

    // First, let's create a new method to organize activities
    private function organizeActivities($student, $templateConfig)
    {
        $defaultStyle = $templateConfig['style'] ?? [
            'text_color' => 'warning',
            'alignment' => 'center'
        ];

        $activitiesConfig = [
            'enabled' => true,
            'layout' => 'side-by-side',
            'spacing' => 'normal',
            'table_style' => $templateConfig['table_style'] ?? [
                'font_size' => '0.875rem',
                'cell_padding' => '0.75rem',
                'row_height' => '2.5rem'
            ],
            'sections' => [
                [
                    'title' => 'Activities',
                    'enabled' => true,
                    'type' => 'rating',
                    'style' => [
                        'background' => 'light',
                        'shadow' => true
                    ],
                    'columns' => [
                        ['key' => 'name', 'label' => 'Activity/Trait'],
                        ['key' => 'rating', 'label' => 'Rating'],
                        ['key' => 'performance', 'label' => 'Performance'],
                    ],
                    'fields' => []
                ],
                [
                    'title' => 'Behavioral Traits',
                    'enabled' => true,
                    'type' => 'rating',
                    'style' => [
                        'background' => 'light',
                        'shadow' => true
                    ],
                    'columns' => [
                        ['key' => 'name', 'label' => 'Activity/Trait'],
                        ['key' => 'rating', 'label' => 'Rating'],
                        ['key' => 'performance', 'label' => 'Performance'],
                    ],
                    'fields' => []
                ]

            ]
        ];

        // Process Activities
        foreach ($student->termActivities as $activity) {
            // Check if this activity exists in template config
            $templateSection = collect($templateConfig['sections'] ?? [])
                ->first(fn($section) => strtolower($section['title']) === 'activities');

            if ($templateSection) {
                $templateField = collect($templateSection['fields'] ?? [])
                    ->first(fn($field) => strtolower($field['name']) === strtolower($activity->activityType->name));
            }

            // Only add if the field is enabled in template or not specified
            if (!$templateField || ($templateField['enabled'] ?? true)) {
                $fieldStyle = array_merge(
                    $defaultStyle,
                    $templateField['style'] ?? []
                );

                $activitiesConfig['sections'][0]['fields'][] = [
                    'name' => $activity->activityType->name,
                    'enabled' => $templateField['enabled'] ?? true,
                    'type' => 'rating',
                    'value' => [
                        'rating' => $activity->rating,
                        'performance' => $this->gradeService->getRatingPerformance($activity->rating)
                    ],
                    'style' => $fieldStyle
                ];
            }
        }

        // Process Behavioral Traits
        foreach ($student->termTraits as $trait) {
            // Check if this trait exists in template config
            $templateSection = collect($templateConfig['sections'] ?? [])
                ->first(fn($section) => strtolower($section['title']) === 'behavioral traits');

            if ($templateSection) {
                $templateField = collect($templateSection['fields'] ?? [])
                    ->first(fn($field) => strtolower($field['name']) === strtolower($trait->behavioralTrait->name));
            }

            // Only add if the field is enabled in template or not specified
            if (!$templateField || ($templateField['enabled'] ?? true)) {
                $fieldStyle = array_merge(
                    $defaultStyle,
                    $templateField['style'] ?? []
                );

                $activitiesConfig['sections'][1]['fields'][] = [
                    'name' => $trait->behavioralTrait->name,
                    'enabled' => $templateField['enabled'] ?? true,
                    'type' => 'rating',
                    'value' => [
                        'rating' => $trait->rating,
                        'performance' => $this->gradeService->getRatingPerformance($trait->rating)
                    ],
                    'style' => $fieldStyle
                ];
            }
        }

        // Apply section-level configuration
        foreach ($activitiesConfig['sections'] as $index => &$section) {
            $templateSection = collect($templateConfig['sections'] ?? [])
                ->first(fn($s) => strtolower($s['title']) === strtolower($section['title']));

            if ($templateSection) {
                $section['enabled'] = $templateSection['enabled'] ?? true;
                $section['style'] = array_merge(
                    $section['style'],
                    $templateSection['style'] ?? []
                );
            }
        }

        return $activitiesConfig;
    }


    public function generateReport(): void
    {
        $startTime = microtime(true);
        $data = $this->form->getState();

        try {
            $student = Student::with([
                'classRoom',
                'admission',
                'grades' => function ($query) use ($data) {
                    $query->where([
                        'term_id' => $data['term_id'],
                        'academic_session_id' => $data['academic_session_id'],
                        'is_published' => true
                    ])->with('assessmentType');
                },
                'termActivities' => function ($query) use ($data) {
                    $query->where([
                        'term_id' => $data['term_id'],
                        'academic_session_id' => $data['academic_session_id']
                    ])->with('activityType');
                },
                'termTraits' => function ($query) use ($data) {
                    $query->where([
                        'term_id' => $data['term_id'],
                        'academic_session_id' => $data['academic_session_id']
                    ])->with('behavioralTrait');
                }
            ])->findOrFail($data['student_id']);

            // Verify student belongs to current school
            if ($student->school_id !== $this->school->id) {
                throw new \Exception('Unauthorized access to student record');
            }

            $reportData = $this->reportCardService->generateReport(
                $student,
                $data['term_id'],
                $data['academic_session_id'],
                $data['template_id'] ?? null
            );

            // Get template configuration
            $template = $reportData['template'];
            $templateConfig = $template->getActivitiesConfig();

            // Generate organized activities and traits
            $activitiesConfig = $this->organizeActivities($student, $templateConfig);

            // Update template with new config
            $template->update([
                'activities_config' => $activitiesConfig
            ]);


            // Match the structure with what GradeService returns
            $this->report = [
                'template' => $reportData['template'],
                'basic_info' => $reportData['basic_info'],
                'attendance' => [
                    'school_days' => $reportData['attendance']['total_days'] ?? $reportData['attendance']['school_days'] ?? 0,
                    'days_present' => $reportData['attendance']['present'] ?? 0,
                    'days_absent' => $reportData['attendance']['absent'] ?? 0,
                    'days_late' => $reportData['attendance']['late'] ?? 0,
                    'days_excused' => $reportData['attendance']['excused'] ?? 0,
                    'attendance_percentage' => $reportData['attendance']['attendance_percentage'] ?? 0,
                ],
                'academic_info' => $reportData['academic_info'],
                'subjects' => $reportData['subjects'],
                // Updated to match the new structure from GradeService
                'term_summary' => $reportData['summary'] ?? $reportData['term_summary'] ?? [],
                'comments' => $reportData['comments'],
                'activities' => $activitiesConfig['sections'],
                'generated_at' => now()
            ];
            // dd($this->report);
            $duration = microtime(true) - $startTime;
            $fromCache = $duration < 0.1;

            Notification::make()
                ->success()
                ->title('Report Generated')
                ->body(sprintf(
                    'Generated in %.2fs %s',
                    $duration,
                    $fromCache ? '(from cache)' : '(fresh)'
                ))
                ->send();
        } catch (\Exception $e) {
            $this->report = [
                'error' => 'Failed to generate report: ' . $e->getMessage()
            ];

            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to generate report: ' . $e->getMessage())
                ->send();

            // Add logging for debugging
            Log::error('Report generation failed', [
                'student_id' => $data['student_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reportData' => $reportData ?? null // Log the report data for debugging
            ]);
        }
    }
    public function downloadReport()
    {
        try {
            $school = $this->school;
            $student = Student::findOrFail($this->data['student_id']);

            // Get and prepare school logo data
            $schoolLogo = null;
            if ($school->logo) {
                $logoPath = storage_path('app/public/' . $school->logo);
                if (File::exists($logoPath)) {
                    // Convert to base64 for reliable PDF embedding
                    $logoData = base64_encode(File::get($logoPath));
                    $mimeType = File::mimeType($logoPath);
                    $schoolLogo = "data:{$mimeType};base64,{$logoData}";
                }
            }

            // Prepare signatures in base64
            $signatures = [
                'class_teacher' => null,
                'principal' => null
            ];

            foreach (['class_teacher', 'principal'] as $type) {
                if (!empty($this->report['comments'][$type]['digital_signature']['signature_url'])) {
                    // Extract the path after '/storage/'
                    $url = $this->report['comments'][$type]['digital_signature']['signature_url'];
                    $parsedUrl = parse_url($url);
                    $pathParts = explode('/storage/', $parsedUrl['path']);
                    $signaturePath = end($pathParts);

                    $fullPath = storage_path('app/public/' . $signaturePath);

                    if (File::exists($fullPath)) {
                        $signatureData = base64_encode(File::get($fullPath));

                        $mimeType = File::mimeType($fullPath);
                        $signatures[$type] = "data:{$mimeType};base64,{$signatureData}";
                        // dd($signatures[$type]);
                    }
                }
            }

            $fileName = sprintf(
                '%s-%s-%s-%s.pdf',
                Str::slug($school->name),
                Str::slug($student->full_name),
                $this->data['term_id'],
                now()->format('Ymd-His')
            );

            $directory = storage_path("app/public/reports/{$school->slug}/" . date('Y/m'));
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $filePath = $directory . '/' . $fileName;


            try {
                $pdf = Pdf::view('pdfs.term-report-card-pdf', [
                    'report' => $this->report,
                    'school' => $school,
                    'student' => $student,
                    'schoolLogo' => $schoolLogo, // Pass the prepared logo
                    'signatures' => $signatures,
                    'isPdfMode' => true,
                ])
                    ->format('a4')
                    ->withBrowsershot(function (Browsershot $browsershot) {
                        $browsershot->setChromePath(config('app.chrome_path'))
                            ->format('A4')
                            ->margins(5, 5, 5, 5)
                            ->showBackground()
                            ->waitUntilNetworkIdle()
                            ->scale(1)
                            ->preferCssPageSize(true)
                            ->timeout(120)
                            ->setNodeBinary(config('browsershot.node_binary', '/usr/bin/node'))
                            ->setNpmBinary(config('browsershot.npm_binary', '/usr/bin/npm'));
                    });
                // dd($pdf);
                $pdf->save($filePath);

                if (!File::exists($filePath)) {
                    throw new \Exception("PDF file was not created at: {$filePath}");
                }

                Log::info('PDF Generated Successfully', [
                    'file' => $filePath,
                    'size' => File::size($filePath),
                    'logo_included' => !is_null($schoolLogo)
                ]);

                Notification::make()
                    ->title('Report generated successfully')
                    ->success()
                    ->send();

                return response()->download($filePath, $fileName, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ])->deleteFileAfterSend(true);
            } catch (\Exception $e) {
                Log::error('PDF Generation Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'school' => $school->slug,
                    'student_id' => $student->id,
                    'logo_status' => !is_null($schoolLogo) ? 'included' : 'not_included'
                ]);

                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Report Download Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'student_id' => $this->data['student_id'] ?? null,
                'school' => $school->slug ?? null
            ]);

            Notification::make()
                ->title('Error generating report')
                ->body('An error occurred while generating the PDF. Please try again.')
                ->danger()
                ->persistent()
                ->send();

            return null;
        }
    }

    protected function getHeaderActions(): array
    {

        return [

            BulkDownloadReportCards::make(),

            \Filament\Actions\Action::make('previewPdf')
                ->label('Preview Report')
                ->icon('heroicon-o-eye')
                ->visible(fn() => filled($this->report))
                ->url(fn() => route('report-cards.preview', [
                    'tenant' => $this->school->slug,
                    'student' => $this->data['student_id'],
                    'term_id' => $this->data['term_id'],
                    'session_id' => $this->data['academic_session_id'],
                    'template_id' => $this->data['template_id'] ?? null,
                ]))
                ->openUrlInNewTab(),

            \Filament\Actions\Action::make('downloadPdf')
                ->label('Download Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn() => filled($this->report))
                ->action('downloadReport')
                ->color('success'),
        ];
    }

    protected function hasInfolist(): bool
    {
        return false;
    }
}
