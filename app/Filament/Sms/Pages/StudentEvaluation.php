<?php

namespace App\Filament\Sms\Pages;

use App\Models\Term;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\ClassRoom;
use App\Models\ActivityType;
use App\Services\GradeService;
use Filament\Facades\Filament;
use App\Helpers\CommentOptions;
use App\Models\AcademicSession;
use App\Models\BehavioralTrait;
use App\Models\StudentTermTrait;
use Filament\Infolists\Infolist;
use Livewire\Attributes\Computed;
use App\Models\StudentTermComment;
use Illuminate\Support\Facades\DB;
use App\Models\StudentTermActivity;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Infolists\Components\Group as infoListGroup;
use Filament\Infolists\Components\Section as infoListSection;

class StudentEvaluation extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static string $view = 'filament.sms.pages.student-evaluations';

    public ?array $data = [];
    public ?array $termSummary = null;
    public $selectedClassId = null;
    protected GradeService $gradeService;

    public function boot(GradeService $gradeService)
    {
        $this->gradeService = $gradeService;
    }


    public function mount(): void
    {

        $this->form->fill();
    }


    public function form(Form $form): Form
    {
        $student = Student::find($this->data['student_id'] ?? null);
        return $form
            ->schema([
                // Student Selection Section
                Section::make('Select Student')
                    ->description('Choose the class and student for evaluation')
                    ->schema([
                        Select::make('class_room_id')
                            ->label('Class')
                            ->options(function () {
                                $query = ClassRoom::query();
                                
                                // If user is a teacher, only show assigned classes
                                if (auth()->user()->hasRole('teacher')) {
                                    $teacher = Teacher::where('staff_id', auth()->user()->staff->id)->first();
                                    if ($teacher) {
                                        $query->whereHas('teachers', function ($q) use ($teacher) {
                                            $q->where('teachers.id', $teacher->id);
                                        });
                                    }
                                }
                                
                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $this->selectedClassId = $state;
                                $set('student_id', null);
                            }),

                        Select::make('student_id')
                            ->label('Student')
                            ->options(function (Get $get) {
                                if (!$get('class_room_id')) {
                                    return [];
                                }
                                
                                return Student::query()
                                    ->where('class_room_id', $get('class_room_id'))
                                    ->get()
                                    ->mapWithKeys(fn($student) => [
                                        $student->id => $student->full_name
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->loadTermSummary();
                                }
                            })
                            ->visible(fn (Get $get): bool => filled($get('class_room_id')))
                            ->columnSpan(2),

                        Select::make('academic_session_id')
                            ->label('Academic Session')
                            ->options(fn() => AcademicSession::where('school_id', Filament::getTenant()->id)->pluck('name', 'id'))
                            ->default(fn() => config('app.current_session')->id ?? null)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn() => $this->loadTermSummary()),

                        Select::make('term_id')
                            ->label('Term')
                            ->options(fn() => Term::where('school_id', Filament::getTenant()->id)->pluck('name', 'id'))
                            // ->default(fn() => config('app.current_term')->id ?? null)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn() => $this->loadTermSummary()),
                    ])
                    ->columns(4),

                // Student Info Display Section


                // Student Info Display Section using a custom view
                View::make('filament.forms.components.student-info-display')
                    ->visible(fn() => filled($this->data['student_id']))
                    ->columnSpan('full'),

                // Main content in tabs
                Tabs::make('Evaluation')
                    ->tabs([
                        // Tab 1: Activities & Performance
                        Tabs\Tab::make('Activities')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Section::make('Academic & Extra-Curricular Activities')
                                    ->description('Rate student\'s performance in various activities')
                                    ->schema([
                                        Repeater::make('activities')
                                            ->schema([
                                                Select::make('activity_type_id')
                                                    ->label('Activity')
                                                    ->options(fn() => ActivityType::where('school_id', Filament::getTenant()->id)
                                                        ->get()
                                                        ->groupBy('category')
                                                        ->map(fn($group) => $group->pluck('name', 'id'))
                                                        ->toArray())
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(2),

                                                Select::make('rating')
                                                    ->label('Rating')
                                                    ->options([
                                                        1 => '★ Poor',
                                                        2 => '★★ Fair',
                                                        3 => '★★★ Good',
                                                        4 => '★★★★ Very Good',
                                                        5 => '★★★★★ Excellent'
                                                    ])
                                                    ->required()
                                                    ->default(null),

                                                TextInput::make('remark')
                                                    ->label('Comments')
                                                    ->maxLength(255),
                                            ])
                                            ->columns(4)
                                            ->collapsible()
                                            ->collapsed()
                                            ->itemLabel(
                                                fn($state) =>
                                                ActivityType::find($state['activity_type_id'])?->name ?? 'New Activity'
                                            )
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Activity')
                                            ->reorderableWithButtons(),
                                    ]),
                            ]),

                        // Tab 2: Behavioral Assessment
                        Tabs\Tab::make('Behavior')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Behavioral Traits Assessment')
                                    ->description('Evaluate student\'s behavioral characteristics')
                                    ->schema([
                                        Repeater::make('traits')
                                            ->schema([
                                                Select::make('behavioral_trait_id')
                                                    ->label('Trait')
                                                    ->options(fn() => BehavioralTrait::where('school_id', Filament::getTenant()->id)
                                                        ->get()
                                                        ->groupBy('category')
                                                        ->map(fn($group) => $group->pluck('name', 'id'))
                                                        ->toArray())
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(2),

                                                Select::make('rating')
                                                    ->label('Rating')
                                                    ->options([
                                                        1 => '★ Poor',
                                                        2 => '★★ Fair',
                                                        3 => '★★★ Good',
                                                        4 => '★★★★ Very Good',
                                                        5 => '★★★★★ Excellent'
                                                    ])
                                                    ->required()
                                                    ->default(null),

                                                TextInput::make('remark')
                                                    ->label('Comments')
                                                    ->maxLength(255),
                                            ])
                                            ->columns(4)
                                            ->collapsible()
                                            ->collapsed()
                                            ->itemLabel(
                                                fn($state) =>
                                                BehavioralTrait::find($state['behavioral_trait_id'])?->name ?? 'New Trait'
                                            )
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Behavioral Trait')
                                            ->reorderableWithButtons(),
                                    ]),
                            ]),

                        // Tab 3: Comments
                        Tabs\Tab::make('Comments')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make("Class Teacher's Comment")
                                    ->description('Provide your evaluation as the class teacher')
                                    ->schema([
                                        Select::make('class_teacher_comment_category')
                                            ->label('Overall Performance Category')
                                            ->options([
                                                'excellent' => 'Excellent',
                                                'very_good' => 'Very Good',
                                                'good' => 'Good',
                                                'average' => 'Average',
                                                'needs_improvement' => 'Needs Improvement'
                                            ])
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set) => $set('class_teacher_comment', null)),

                                        Select::make('class_teacher_comment')
                                            ->label('Select Template Comment')
                                            ->options(function (Get $get) {
                                                $category = $get('class_teacher_comment_category');
                                                if (!$category) return [];
                                                return collect(CommentOptions::getTeacherCommentsByCategory($category))
                                                    ->mapWithKeys(fn($comment) => [$comment => $comment]);
                                            })
                                            ->searchable()
                                            ->visible(fn(Get $get) => filled($get('class_teacher_comment_category')))
                                            ->live(),

                                        Textarea::make('custom_class_teacher_comment')
                                            ->label('Custom Comment')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                Section::make("Principal's Comment")
                                    ->description('Principal\'s evaluation and remarks')
                                    ->schema([
                                        Select::make('principal_comment_category')
                                            ->label('Overall Assessment Category')
                                            ->options([
                                                'excellent' => 'Excellent',
                                                'very_good' => 'Very Good',
                                                'good' => 'Good',
                                                'average' => 'Average',
                                                'needs_improvement' => 'Needs Improvement'
                                            ])
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set) => $set('principal_comment', null)),

                                        Select::make('principal_comment')
                                            ->label('Select Template Comment')
                                            ->options(function (Get $get) {
                                                $category = $get('principal_comment_category');
                                                if (!$category) return [];
                                                return collect(CommentOptions::getPrincipalCommentsByCategory($category))
                                                    ->mapWithKeys(fn($comment) => [$comment => $comment]);
                                            })
                                            ->searchable()
                                            ->visible(fn(Get $get) => filled($get('principal_comment_category')))
                                            ->live(),

                                        Textarea::make('custom_principal_comment')
                                            ->label('Custom Comment')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpan('full'),
            ])
            ->columns(3)
            ->statePath('data');
    }

    protected function loadTermSummary(): void
    {
        $data = $this->form->getState();

        // Reset term summary when any required field is missing
        // if (!($data['student_id'] ?? null) || !($data['academic_session_id'] ?? null) ) {
        //     $this->termSummary = null;
        //     $this->loadExistingEvaluation();
        //     return;
        // }

        $student = Student::find($data['student_id']);
        // if (!$student) {
        //     $this->termSummary = null;
        //     $this->loadExistingEvaluation();
        //     return;
        // }

        try {
            // Get fresh term data
            $this->termSummary = $this->gradeService->generateTermReport(
                $student,
                $data['term_id'],
                $data['academic_session_id']
            );
        } catch (\Exception $e) {
            // Handle case where no data exists for selected term
            $this->termSummary = [
                'basic_info' => [
                    'admission' => $student->admission_number,
                    'class' => $student->classRoom->name,
                    'id' => $student->id
                ],
                'academic_info' => [
                    'session' => [
                        'id' => $data['academic_session_id'],
                        'name' => AcademicSession::find($data['academic_session_id'])?->name ?? 'Unknown'
                    ],
                    'term' => [
                        'id' => $data['term_id'],
                        'name' => Term::find($data['term_id'])?->name ?? 'Unknown'
                    ]
                ],
                'summary' => [
                    'average' => 0,
                    'position' => 0,
                    'class_size' => 0,
                    'total_subjects' => 0
                ],
                'attendance' => [
                    'school_days' => 0,
                    'present' => 0
                ]
            ];
        }

        // Reload evaluation data for the new term
        $this->loadExistingEvaluation();
    }

    protected function loadExistingEvaluation(): void
    {
        $data = $this->form->getState();

        if (!filled($data['student_id']) || !filled($data['academic_session_id']) || !filled($data['term_id'])) {
            return;
        }

        // Get existing activities
        $existingActivities = StudentTermActivity::where([
            'student_id' => $data['student_id'],
            'academic_session_id' => $data['academic_session_id'],
            'term_id' => $data['term_id'],
        ])
            ->with('activityType')
            ->get()
            ->map(function ($activity) {
                return [
                    'activity_type_id' => $activity->activity_type_id,
                    'rating' => $activity->rating,
                    'remark' => $activity->remark
                ];
            })
            ->toArray();

        // Get existing traits
        $existingTraits = StudentTermTrait::where([
            'student_id' => $data['student_id'],
            'academic_session_id' => $data['academic_session_id'],
            'term_id' => $data['term_id'],
        ])
            ->with('behavioralTrait')
            ->get()
            ->map(function ($trait) {
                return [
                    'behavioral_trait_id' => $trait->behavioral_trait_id,
                    'rating' => $trait->rating,
                    'remark' => $trait->remark
                ];
            })
            ->toArray();

        // If no existing activities, load 5 default ones with preset ratings
        if (empty($existingActivities)) {
            $defaultRatings = [5, 4, 4, 3, 5]; // Preset ratings for default items

            $existingActivities = ActivityType::where('school_id', Filament::getTenant()->id)
                ->where('is_default', true)
                ->orderBy('category')
                ->orderBy('display_order')
                ->limit(5)
                ->get()
                ->map(function ($type, $index) use ($defaultRatings) {
                    return [
                        'activity_type_id' => $type->id,
                        'rating' => $defaultRatings[$index], // Assign preset rating
                        'remark' => null
                    ];
                })
                ->toArray();
        }

        // If no existing traits, provide 5 default traits
        if (empty($existingTraits)) {
            $defaultRatings = [5, 4, 4, 3, 5]; // Preset ratings for default items
            $defaultTraits = BehavioralTrait::where('school_id', Filament::getTenant()->id)
                ->where('is_default', true)
                ->orderBy('category')
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(function ($trait, $index) use ($defaultRatings) {
                    return [
                        'behavioral_trait_id' => $trait->id,
                        'rating' => $defaultRatings[$index],
                        'remark' => null
                    ];
                })
                ->toArray();
        }

        // Get existing comments
        $comment = StudentTermComment::where([
            'student_id' => $data['student_id'],
            'academic_session_id' => $data['academic_session_id'],
            'term_id' => $data['term_id'],
        ])->first();

        // Fill form with the appropriate data
        $this->form->fill([
            ...$data,
            'activities' => !empty($existingActivities) ? $existingActivities : ($defaultActivities ?? []),
            'traits' => !empty($existingTraits) ? $existingTraits : ($defaultTraits ?? []),
            'custom_class_teacher_comment' => $comment?->class_teacher_comment,
            'custom_principal_comment' => $comment?->principal_comment,
        ]);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            DB::transaction(function () use ($data) {
                // Get staff details
                $staff = Staff::where('user_id', Auth::id())->first();

                if (!$staff) {
                    throw new \Exception('Staff record not found');
                }

                // Determine the final comments (use custom if provided, otherwise use predefined)
                $teacherComment = $data['custom_class_teacher_comment'] ?? $data['class_teacher_comment'] ?? null;
                $principalComment = $data['custom_principal_comment'] ?? $data['principal_comment'] ?? null;

                // Save/Update comments
                StudentTermComment::updateOrCreate(
                    [
                        'student_id' => $data['student_id'],
                        'academic_session_id' => $data['academic_session_id'],
                        'term_id' => $data['term_id'],
                    ],
                    [
                        'school_id' => Filament::getTenant()->id,
                        'class_teacher_comment' => $teacherComment,
                        'class_teacher_id' => Auth::id(),
                        'principal_comment' => $principalComment,
                        'principal_id' => filled($principalComment) ? Auth::id() : null,
                    ]
                );

                // Delete and recreate activities
                StudentTermActivity::where([
                    'student_id' => $data['student_id'],
                    'academic_session_id' => $data['academic_session_id'],
                    'term_id' => $data['term_id'],
                ])->delete();

                // Inside the save method's foreach loop for activities
                foreach ($data['activities'] ?? [] as $activity) {
                    if (isset($activity['rating'])) { // Only create if rating exists
                        StudentTermActivity::create([
                            'school_id' => Filament::getTenant()->id,
                            'student_id' => $data['student_id'],
                            'academic_session_id' => $data['academic_session_id'],
                            'term_id' => $data['term_id'],
                            'activity_type_id' => $activity['activity_type_id'],
                            'rating' => $activity['rating'],
                            'remark' => $activity['remark'] ?? null,
                            'recorded_by' => Auth::id(),
                        ]);
                    }
                }

                // Delete and recreate traits
                StudentTermTrait::where([
                    'student_id' => $data['student_id'],
                    'academic_session_id' => $data['academic_session_id'],
                    'term_id' => $data['term_id'],
                ])->delete();

                // Inside save method
                foreach ($data['traits'] ?? [] as $trait) {
                    if (isset($trait['rating'])) { // Only create if rating exists
                        StudentTermTrait::create([
                            'school_id' => Filament::getTenant()->id,
                            'student_id' => $data['student_id'],
                            'academic_session_id' => $data['academic_session_id'],
                            'term_id' => $data['term_id'],
                            'behavioral_trait_id' => $trait['behavioral_trait_id'],
                            'rating' => $trait['rating'],
                            'remark' => $trait['remark'] ?? null,
                            'recorded_by' => Auth::id(),
                        ]);
                    }
                }
            });

            Notification::make()
                ->success()
                ->title('Evaluation Saved')
                ->body('Student evaluation has been saved successfully.')
                ->send();

            $this->loadExistingEvaluation();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('There was an error saving the evaluation: ' . $e->getMessage())
                ->send();

            throw $e;
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Evaluation')
                ->action('save')
                ->color('primary')
                ->icon('heroicon-o-check')
        ];
    }
}
