<?php

namespace App\Filament\Sms\Pages;

use App\Models\Term;
use App\Models\Staff;
use App\Models\Student;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\ActivityType;
use App\Services\GradeService;
use Filament\Facades\Filament;
use App\Helpers\CommentOptions;
use App\Models\AcademicSession;
use App\Models\BehavioralTrait;
use App\Models\StudentTermTrait;
use App\Models\StudentTermComment;
use Illuminate\Support\Facades\DB;
use App\Models\StudentTermActivity;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class StudentEvaluation extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static string $view = 'filament.sms.pages.student-evaluations';

    public ?array $data = [];
    public ?array $termSummary = null;
    // protected GradeService $gradeService;

    // public function boot(GradeService $gradeService)
    // {
    //     // Initialize service in boot instead
    //     $this->gradeService = $gradeService;
    // }


    public function mount(): void
    {

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Student Information')
                    ->description('Select student and academic period')
                    ->schema([
                        Select::make('student_id')
                            ->label('Student')
                            ->options(function () {
                                return Student::query()
                                    ->where('school_id', Filament::getTenant()->id)
                                    ->with(['classRoom'])
                                    ->get()
                                    ->mapWithKeys(fn($student) => [
                                        $student->id => "{$student->full_name} - {$student->classRoom->name}"
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->loadTermSummary();
                                }
                            }),

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
                            ->default(fn() => config('app.current_term')->id ?? null)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn() => $this->loadTermSummary()),
                    ])
                    ->columns(3),

                // Term Summary Section (if available)
                Section::make('Term Summary')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextInput::make('total_score')
                                    ->disabled(),
                                TextInput::make('average_score')
                                    ->disabled(),
                                TextInput::make('position')
                                    ->disabled(),
                                TextInput::make('class_size')
                                    ->disabled(),
                            ])
                            ->columns(4),
                    ])
                    ->visible(fn() => filled($this->termSummary)),

                Group::make()
                    ->schema([
                        Section::make('Activities & Performance')
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
                                            ->preload(),

                                        Select::make('rating')
                                            ->label('Performance Rating')
                                            ->options([
                                                1 => '★ Poor',
                                                2 => '★★ Fair',
                                                3 => '★★★ Good',
                                                4 => '★★★★ Very Good',
                                                5 => '★★★★★ Excellent'
                                            ])
                                            ->required()
                                            ->default(null), // Add default value to prevent undefined key

                                        TextInput::make('remark')
                                            ->label('Additional Comments')
                                            ->maxLength(255),
                                    ])
                                    ->columns(3)
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(
                                        fn($state) =>
                                        ActivityType::find($state['activity_type_id'])?->name ?? 'Activity'
                                    )
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Activity'),
                            ]),

                        Section::make('Behavioral Assessment')
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
                                            ->preload(),

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
                                            ->default(null), // Add default value to prevent undefined key

                                        TextInput::make('remark')
                                            ->label('Comments')
                                            ->maxLength(255),
                                    ])
                                    ->columns(3)
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(
                                        fn($state) =>
                                        BehavioralTrait::find($state['behavioral_trait_id'])?->name ?? 'Trait'
                                    )
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Trait'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        // Class Teacher's Comment Section
                        Section::make("Class Teacher's Comment")
                            ->description('Select from predefined comments or write a custom comment')
                            ->schema([
                                Select::make('class_teacher_comment_category')
                                    ->label('Category')
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
                                    ->label('Select Predefined Comment')
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
                            ]),

                        // Principal's Comment Section
                        Section::make("Principal's Comment")
                            ->description('Select from predefined comments or write a custom comment')
                            ->schema([
                                Select::make('principal_comment_category')
                                    ->label('Category')
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
                                    ->label('Select Predefined Comment')
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
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3)
            ->statePath('data');
    }

    protected function loadTermSummary(): void
    {
        $data = $this->form->getState();

        if (!($data['student_id'] ?? null) || !($data['academic_session_id'] ?? null) || !($data['term_id'] ?? null)) {
            $this->termSummary = null;
            return;
        }

        $student = Student::find($data['student_id']);
        if (!$student) {
            $this->termSummary = null;
            return;
        }


        $this->loadExistingEvaluation();
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

    // protected function loadExistingEvaluation(): void
    // {
    //     $data = $this->form->getState();

    //     if (!($data['student_id'] ?? null) || !($data['academic_session_id'] ?? null) || !($data['term_id'] ?? null)) {
    //         return;
    //     }

    //     // Load activities
    //     $activities = StudentTermActivity::where([
    //         'student_id' => $data['student_id'],
    //         'academic_session_id' => $data['academic_session_id'],
    //         'term_id' => $data['term_id'],
    //     ])->get()->map(fn($activity) => [
    //         'activity_type_id' => $activity->activity_type_id,
    //         'rating' => $activity->rating,
    //         'remark' => $activity->remark,
    //     ])->toArray();

    //     // Load traits
    //     $traits = StudentTermTrait::where([
    //         'student_id' => $data['student_id'],
    //         'academic_session_id' => $data['academic_session_id'],
    //         'term_id' => $data['term_id'],
    //     ])->get()->map(fn($trait) => [
    //         'behavioral_trait_id' => $trait->behavioral_trait_id,
    //         'rating' => $trait->rating,
    //         'remark' => $trait->remark,
    //     ])->toArray();

    //     // Load existing comments
    //     $comment = StudentTermComment::where([
    //         'student_id' => $data['student_id'],
    //         'academic_session_id' => $data['academic_session_id'],
    //         'term_id' => $data['term_id'],
    //     ])->first();

    //     // Initialize comment data
    //     $commentData = [
    //         'activities' => $activities,
    //         'traits' => $traits,
    //         'class_teacher_comment_category' => null,
    //         'principal_comment_category' => null,
    //         'class_teacher_comment' => null,
    //         'principal_comment' => null,
    //         'custom_class_teacher_comment' => $comment?->class_teacher_comment ?? null,
    //         'custom_principal_comment' => $comment?->principal_comment ?? null,
    //     ];

    //     // Fill the form with existing data
    //     $this->form->fill([
    //         ...$data,
    //         ...$commentData,
    //         'activities' => $activities,
    //         'traits' => $traits,
    //         'custom_class_teacher_comment' => $comment?->class_teacher_comment,
    //         'custom_principal_comment' => $comment?->principal_comment,
    //     ]);
    // }

    protected function loadExistingEvaluation(): void
    {
        $data = $this->form->getState();

        if (!filled($data['student_id']) || !filled($data['academic_session_id']) || !filled($data['term_id'])) {
            return;
        }

        // Get or create default activities
        $defaultActivities = ActivityType::where('school_id', Filament::getTenant()->id)
            ->where('is_default', true)
            ->orderBy('category')
            ->orderBy('display_order')
            ->get()
            ->map(function ($type) use ($data) {
                // Find existing rating or create blank
                $activity = StudentTermActivity::firstOrNew([
                    'student_id' => $data['student_id'],
                    'academic_session_id' => $data['academic_session_id'],
                    'term_id' => $data['term_id'],
                    'activity_type_id' => $type->id,
                    'school_id' => Filament::getTenant()->id
                ]);

                return [
                    'activity_type_id' => $type->id,
                    'rating' => $activity->exists ? $activity->rating : null,
                    'remark' => $activity->exists ? $activity->remark : null
                ];
            })
            ->toArray();

        // Do same for behavioral traits
        $defaultTraits = BehavioralTrait::where('school_id', Filament::getTenant()->id)
            ->where('is_default', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function ($trait) use ($data) {
                $rating = StudentTermTrait::firstOrNew([
                    'student_id' => $data['student_id'],
                    'academic_session_id' => $data['academic_session_id'],
                    'term_id' => $data['term_id'],
                    'behavioral_trait_id' => $trait->id,
                    'school_id' => Filament::getTenant()->id
                ]);

                return [
                    'behavioral_trait_id' => $trait->id,
                    'rating' => $rating->exists ? $rating->rating : null,
                    'remark' => $rating->exists ? $rating->remark : null
                ];
            })
            ->toArray();

        // Load comments
        $comment = StudentTermComment::where([
            'student_id' => $data['student_id'],
            'academic_session_id' => $data['academic_session_id'],
            'term_id' => $data['term_id'],
        ])->first();

        // Fill form with all data
        $this->form->fill([
            ...$data,
            'activities' => $defaultActivities,
            'traits' => $defaultTraits,
            'custom_class_teacher_comment' => $comment?->class_teacher_comment,
            'custom_principal_comment' => $comment?->principal_comment,
        ]);
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
