<?php

namespace App\Filament\Sms\Pages;


use Closure;
use App\Models\Term;
use App\Models\Draft;
use App\Models\Status;
use App\Models\Student;
use App\Models\Subject;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\ClassRoom;
use Livewire\Attributes\On;
use App\Models\StudentGrade;
use Filament\Actions\Action;
use App\Models\AssessmentType;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class BulkGradeStudents extends Page
{
    use HasPageShield;
    // Navigation properties
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static string $view = 'filament.pages.bulk-grade-students';
    protected static ?string $title = 'Bulk Student Grading';
    protected static ?int $navigationSort = 1;

    // Form and Draft properties
    public ?array $data = [];


    // Auto-save properties
    // Add these properties
    public bool $autoSaveEnabled = false;
    public bool $hasDraft = false;
    public ?string $lastAutoSave = null;
    protected ?Draft $currentDraft = null;

    public $shownEmptyFormNotification = false;

    protected array $queryString = [
        'data.academic_session_id',
        'data.term_id',
    ];


    // Basic mount method
    public function mount(): void
    {
        // Check for existing draft
        $this->currentDraft = Draft::where([
            'school_id' => Filament::getTenant()->id,
            'user_id' => auth()->id(),
            'type' => 'bulk_grades'
        ])->latest('last_modified')->first();

        if ($this->currentDraft) {
            $this->hasDraft = true;
            $this->lastAutoSave = $this->currentDraft->last_modified?->diffForHumans();
        }

        // Initialize form
        $this->form->fill();
    }

    #[On('auto-save-enabled')]
    public function enableAutoSave(): void
    {
        $this->autoSaveEnabled = true;
        Notification::make()
            ->title('Auto-save enabled')
            ->success()
            ->send();
    }

    #[On('auto-save-disabled')]
    public function disableAutoSave(): void
    {
        $this->autoSaveEnabled = false;
        Notification::make()
            ->title('Auto-save disabled')
            ->warning()
            ->send();
    }

    // #[On('save-draft')]
    // public function handleAutoSave()
    // {
    //     if (!$this->autoSaveEnabled) {
    //         return;
    //     }

    //     try {
    //         // Get raw state without validation
    //         $formData = $this->form->getRawState();

    //         // Only save if there's any data to save
    //         if (empty($formData)) {
    //             return;
    //         }

    //         Draft::updateOrCreate(
    //             [
    //                 'school_id' => Filament::getTenant()->id,
    //                 'user_id' => auth()->id(),
    //                 'type' => 'bulk_grades',
    //             ],
    //             [
    //                 'content' => $formData,
    //                 'last_modified' => now(),
    //                 'is_auto_save' => true
    //             ]
    //         );

    //         $this->lastAutoSave = now()->format('H:i:s');
    //         $this->hasDraft = true;

    //         // Optional: Use a less intrusive notification for auto-save
    //         Notification::make()
    //             ->title('Draft saved')
    //             ->success()
    //             ->seconds(2) // Make it disappear quickly
    //             ->send();
    //     } catch (\Exception $e) {
    //         logger()->error('Auto-save failed:', [
    //             'error' => $e->getMessage(),
    //             'user_id' => auth()->id(),
    //             'school_id' => Filament::getTenant()->id
    //         ]);

    //         // Only notify on actual errors, not validation
    //         if (!str_contains($e->getMessage(), 'required')) {
    //             Notification::make()
    //                 ->title('Auto-save failed')
    //                 ->danger()
    //                 ->send();
    //         }
    //     }
    // }


    // In your PHP class
    #[On('save-draft')]
    public function handleAutoSave()
    {
        if (!$this->autoSaveEnabled) {
            return;
        }

        try {
            $formData = $this->form->getRawState();

            if (empty($formData)) {
                if (!$this->shownEmptyFormNotification) {
                    $this->shownEmptyFormNotification = true;
                    // Change how we dispatch the event
                    $this->dispatch('draft-status', data: [
                        'status' => 'empty',
                        'message' => 'Start filling the form to enable auto-save'
                    ]);
                }
                return;
            }

            Draft::updateOrCreate(
                [
                    'school_id' => Filament::getTenant()->id,
                    'user_id' => auth()->id(),
                    'type' => 'bulk_grades',
                ],
                [
                    'content' => $formData,
                    'last_modified' => now(),
                    'is_auto_save' => true
                ]
            );

            $this->lastAutoSave = now()->format('H:i:s');
            $this->hasDraft = true;

            $this->dispatch('draft-status', data: [
                'status' => 'success',
                'message' => 'Draft saved'
            ]);
        } catch (\Exception $e) {
            logger()->error('Auto-save failed:', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'school_id' => Filament::getTenant()->id
            ]);

            $this->dispatch('draft-status', data: [
                'status' => 'error',
                'message' => 'Failed to save draft'
            ]);
        }
    }

    public function restoreDraft(): void
    {
        // Load the draft directly from database instead of relying on property
        $draft = Draft::where([
            'school_id' => Filament::getTenant()->id,
            'user_id' => auth()->id(),
            'type' => 'bulk_grades'
        ])->latest('last_modified')->first();

        if (!$draft) {
            Notification::make()
                ->title('No draft found')
                ->warning()
                ->send();
            return;
        }

        try {
            $content = $draft->content;

            // Fill the form with the draft content
            $this->form->fill($content);

            // Update the properties
            $this->currentDraft = $draft;
            $this->lastAutoSave = $draft->last_modified?->format('H:i:s');

            Notification::make()
                ->title('Draft restored')
                ->success()
                ->send();
        } catch (\Exception $e) {
            logger()->error('Failed to restore draft:', [
                'error' => $e->getMessage(),
                'draft_id' => $draft->id,
                'content' => $content ?? null
            ]);

            Notification::make()
                ->title('Failed to restore draft')
                ->danger()
                ->send();
        }
    }

    public function clearDraft(): void
    {
        // Load the draft directly
        $draft = Draft::where([
            'school_id' => Filament::getTenant()->id,
            'user_id' => auth()->id(),
            'type' => 'bulk_grades'
        ])->latest('last_modified')->first();

        if (!$draft) {
            return;
        }

        try {
            $draft->delete();

            // Reset properties
            $this->currentDraft = null;
            $this->hasDraft = false;
            $this->lastAutoSave = null;

            // Reset the form
            $this->form->fill();

            Notification::make()
                ->title('Draft cleared')
                ->success()
                ->send();
        } catch (\Exception $e) {
            logger()->error('Failed to clear draft:', [
                'error' => $e->getMessage(),
                'draft_id' => $draft->id
            ]);

            Notification::make()
                ->title('Failed to clear draft')
                ->danger()
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Group::make()
                ->schema([
                    Toggle::make('autoSaveEnabled')
                        ->label('Enable Auto-save')
                        ->helperText(fn() => $this->lastAutoSave
                            ? 'Last auto-saved at ' . $this->lastAutoSave
                            : 'Auto-save every minute')
                        ->live() // Make it reactive
                        ->afterStateUpdated(function (bool $state) {
                            $this->autoSaveEnabled = $state;
                            if ($state) {
                                $this->dispatch('auto-save-enabled');
                            } else {
                                $this->dispatch('auto-save-disabled');
                            }
                        }),
                ])
                ->columnSpanFull(),

            // Academic Period Section
            Section::make('Academic Period')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('academic_session_id')
                            ->label('Academic Session')
                            ->options(fn() => AcademicSession::where('school_id', Filament::getTenant()->id)
                                ->pluck('name', 'id'))
                            ->default(fn() => config('app.current_session')->id ?? null)
                            ->required()
                            ->live(),

                        Select::make('term_id')
                            ->label('Term')
                            ->options(fn(Get $get) => Term::where('school_id', Filament::getTenant()->id)
                                ->where('academic_session_id', $get('academic_session_id'))
                                ->pluck('name', 'id'))
                            ->default(fn() => config('app.current_term')->id ?? null)
                            ->required()
                            ->live(),
                    ]),
                ]),

            // Grade Entries Section
            Section::make('Grade Entries')
                ->description('Enter student grades')
                ->schema([
                    Repeater::make('grade_entries')
                        ->cloneable()
                        ->reorderable()
                        ->addActionLabel('Add Grade Entry')
                        ->schema([
                            // Student and Class Selection
                            Grid::make(3)->schema([
                                Select::make('class_room_id')
                                    ->label('Class')
                                    ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('student_id', null)),

                                Select::make('student_id')
                                    ->label('Student')
                                    ->options(fn(Get $get) => $this->getStudentOptions($get('class_room_id')))
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                Select::make('subject_id')
                                    ->label('Subject')
                                    ->options(fn() => Subject::where('school_id', Filament::getTenant()->id)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->live(),
                            ]),

                            // Assessment Scores Repeater
                            Repeater::make('assessment_scores')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Select::make('assessment_type_id')
                                            ->label('Assessment Type')
                                            ->options(fn() => $this->getAssessmentTypeOptions())
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated($this->handleAssessmentTypeChange()),

                                        TextInput::make('score')
                                            ->label('Score')
                                            ->numeric()
                                            ->required()
                                            ->rules($this->getScoreValidationRules()),

                                        DatePicker::make('assessment_date')
                                            ->label('Date')
                                            ->required()
                                            ->default(now())
                                            ->maxDate(now()),

                                        Hidden::make('max_score')
                                            ->dehydrated(false),
                                    ]),
                                ])
                                ->columns(1)
                                ->itemLabel($this->getAssessmentLabel())
                                ->collapsible()
                                ->defaultItems(1),
                        ])
                        ->columns(1)
                        ->itemLabel($this->getGradeEntryLabel())
                        ->collapsible()
                        ->defaultItems(1)
                        ->addActionLabel('Add Student')
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get): bool =>
                filled($get('academic_session_id')) &&
                    filled($get('term_id'))),

            Hidden::make('school_id')
                ->default(fn() => Filament::getTenant()->id),
        ])->statePath('data');
    }

    protected function getAssessmentTypeOptions(): array
    {
        return AssessmentType::where('school_id', Filament::getTenant()->id)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn($type) => [
                $type->id => "{$type->name} (Max: {$type->max_score})"
            ])
            ->toArray();
    }

    protected function getStudentOptions($classRoomId): array
    {
        if (!$classRoomId) return [];

        return Student::where('class_room_id', $classRoomId)
            ->where('status_id', Status::where([
                'type' => 'student',
                'name' => 'active'
            ])->first()?->id)
            ->get()
            ->mapWithKeys(fn($student) => [
                $student->id => $student->admission_number ?
                    "{$student->full_name} ({$student->admission_number})" :
                    $student->full_name
            ])
            ->toArray();
    }

    protected function handleAssessmentTypeChange(): Closure
    {
        return function (Get $get, Set $set) {
            $maxScore = AssessmentType::find($get('assessment_type_id'))?->max_score ?? 0;
            $set('max_score', $maxScore);
        };
    }

    protected function getScoreValidationRules(): array
    {
        return [
            'required',
            'numeric',
            'min:0',
            fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                $maxScore = $get('max_score');
                if ($value > $maxScore) {
                    $fail("Score cannot exceed maximum of {$maxScore}");
                }
            },
        ];
    }

    protected function getAssessmentLabel(): Closure
    {
        return fn(array $state): string =>
        AssessmentType::find($state['assessment_type_id'])?->name ?? 'New Assessment';
    }

    protected function getGradeEntryLabel(): Closure
    {
        return fn(array $state): string =>
        sprintf(
            '%s - %s',
            Student::find($state['student_id'])?->full_name ?? 'Select Student',
            Subject::find($state['subject_id'])?->name ?? 'Select Subject'
        );
    }

    // Grade Saving Logic
    public function saveGrades(): void
    {
        $data = $this->form->getState();
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($data['grade_entries'] as $entryIndex => $entry) {
                // Validate entry
                $entryErrors = $this->validateGradeEntry($entry, $entryIndex);
                if (!empty($entryErrors)) {
                    $errors = array_merge($errors, $entryErrors);
                    continue;
                }

                // Process assessment scores
                foreach ($entry['assessment_scores'] as $scoreIndex => $score) {
                    try {
                        // Check for duplicates before saving
                        if ($this->isDuplicateGrade($entry, $score, $data)) {
                            // Get student and assessment type names for better error message
                            $student = Student::find($entry['student_id']);
                            $assessmentType = AssessmentType::find($score['assessment_type_id']);
                            $subject = Subject::find($entry['subject_id']);

                            $errors[] = "Grade already exists for student '{$student->full_name}' in subject '{$subject->name}' for assessment '{$assessmentType->name}'";
                            continue;
                        }

                        $grade = $this->createGrade($entry, $score, $data);
                    } catch (\Exception $e) {
                        $errors[] = "Error saving grade: {$e->getMessage()}";
                    }
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                $this->handleValidationErrors(implode("\n", $errors));
                return;
            }

            DB::commit();

            // Clear draft after successful save
            $this->clearDraft();

            Notification::make()
                ->success()
                ->title('Grades Saved')
                ->body('Grades have been recorded successfully.')
                ->send();

            $this->redirect(BulkGradeStudents::getUrl());
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Bulk grade save error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    protected function createGrade(array $entry, array $score, array $data): ?StudentGrade
    {
        return StudentGrade::create([
            'school_id' => $data['school_id'],
            'student_id' => $entry['student_id'],
            'subject_id' => $entry['subject_id'],
            'class_room_id' => $entry['class_room_id'],
            'assessment_type_id' => $score['assessment_type_id'],
            'academic_session_id' => $data['academic_session_id'],
            'term_id' => $data['term_id'],
            'score' => $score['score'],
            'remarks' => $entry['remarks'] ?? null,
            'recorded_by' => auth()->id(),
            'assessment_date' => $score['assessment_date'],
            'graded_at' => now(),
        ]);
    }
    
    protected function isDuplicateGrade(array $entry, array $score, array $data): bool
    {
        try {
            $existingGrade = StudentGrade::where([
                'school_id' => $data['school_id'],
                'student_id' => $entry['student_id'],
                'subject_id' => $entry['subject_id'],
                'class_room_id' => $entry['class_room_id'],
                'assessment_type_id' => $score['assessment_type_id'],
                'academic_session_id' => $data['academic_session_id'],
                'term_id' => $data['term_id'],
            ])->first();

            if (!$existingGrade) {
                return false;
            }

            // Get related data for better error message
            $student = Student::find($entry['student_id']);
            $subject = Subject::find($entry['subject_id']);
            $assessmentType = AssessmentType::find($score['assessment_type_id']);

            throw new \Exception(sprintf(
                "Grade already exists for student '%s' in subject '%s' for assessment '%s' with score %.2f",
                $student->full_name,
                $subject->name,
                $assessmentType->name,
                $existingGrade->score
            ));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Grade already exists')) {
                throw $e;
            }

            // For other errors, provide a generic message
            throw new \Exception("Failed to validate duplicate grade: " . $e->getMessage());
        }
    }

    protected function validateGradeEntry(array $entry, int $index): array
    {
        $errors = [];

        try {
            // Required field validations
            if (empty($entry['student_id'])) {
                $errors[] = $this->formatValidationError(
                    "Please select a student",
                    "Entry #$index"
                );
            }

            if (empty($entry['subject_id'])) {
                $errors[] = $this->formatValidationError(
                    "Please select a subject",
                    "Entry #$index"
                );
            }

            if (empty($entry['class_room_id'])) {
                $errors[] = $this->formatValidationError(
                    "Please select a class",
                    "Entry #$index"
                );
            }

            if (empty($entry['assessment_scores'])) {
                $errors[] = $this->formatValidationError(
                    "At least one assessment score is required",
                    "Entry #$index"
                );
            }
        } catch (\Exception $e) {
            $errors[] = $this->formatValidationError(
                $e->getMessage(),
                "Entry #$index"
            );
        }

        return $errors;
    }

    protected function validateAssessmentScore(array $score, int $entryIndex, int $scoreIndex): array
    {
        $errors = [];

        try {
            // Required field validations with specific messages
            if (empty($score['assessment_type_id'])) {
                $errors[] = $this->formatValidationError(
                    "Please select an assessment type",
                    "Score #$scoreIndex in Entry #$entryIndex"
                );
            }

            if (!isset($score['score'])) {
                $errors[] = $this->formatValidationError(
                    "Score value is required",
                    "Score #$scoreIndex in Entry #$entryIndex"
                );
            }

            if (empty($score['assessment_date'])) {
                $errors[] = $this->formatValidationError(
                    "Assessment date is required",
                    "Score #$scoreIndex in Entry #$entryIndex"
                );
            }

            // Validate score against max score
            if (isset($score['assessment_type_id']) && isset($score['score'])) {
                $assessmentType = AssessmentType::find($score['assessment_type_id']);
                if ($assessmentType && $score['score'] > $assessmentType->max_score) {
                    $errors[] = $this->formatValidationError(
                        "Score cannot exceed {$assessmentType->max_score} for {$assessmentType->name}",
                        "Score #$scoreIndex in Entry #$entryIndex"
                    );
                }
            }
        } catch (\Exception $e) {
            $errors[] = $this->formatValidationError(
                $e->getMessage(),
                "Score #$scoreIndex in Entry #$entryIndex"
            );
        }

        return $errors;
    }

    protected function formatValidationError(string $message, string $context): string
    {
        return "{$context}: {$message}";
    }

    protected function handleValidationErrors(string $errorsString): void
    {
        // Split the string into an array of errors
        $errors = explode("\n", $errorsString);
        
        // Separate duplicate errors from other errors
        $duplicateErrors = array_filter($errors, fn($error) => 
            str_contains($error, 'Grade already exists'));
        
        $otherErrors = array_filter($errors, fn($error) => 
            !str_contains($error, 'Grade already exists'));
    
        // Show duplicate errors differently
        if (!empty($duplicateErrors)) {
            Notification::make()
                ->warning()
                ->title('Duplicate Grade Entries Detected')
                ->body(collect($duplicateErrors)
                    ->map(fn($msg) => "• " . trim($msg))
                    ->join("\n"))
                ->persistent()
                ->send();
        }
    
        // Show other errors with support message
        if (!empty($otherErrors)) {
            Notification::make()
                ->danger()
                ->title('Please fix the following errors')
                ->body(
                    collect($otherErrors)
                        ->map(fn($msg) => "• " . trim($msg))
                        ->join("\n") . 
                    "\n\nPlease contact technical support if this issue persists."
                )
                ->persistent()
                ->send();
        }
    }

    protected function handleSaveError(\Exception $e): void
    {
        DB::rollBack();
        logger()->error('Bulk grade save error:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'school_id' => Filament::getTenant()->id
        ]);

        Notification::make()
            ->danger()
            ->title('Error')
            ->body('An error occurred while saving grades. Please try again.')
            ->persistent()
            ->send();
    }

    protected function notifySuccess(array $createdGrades): void
    {
        Notification::make()
            ->success()
            ->title('Grades Saved')
            ->body(sprintf('%d grades have been recorded successfully.', count($createdGrades)))
            ->send();
    }
}
