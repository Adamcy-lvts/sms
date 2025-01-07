<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use App\Models\Term;
use Filament\Tables;
use App\Models\Status;
use App\Models\Student;
use App\Models\Subject;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use App\Models\StudentGrade;
use App\Models\AssessmentType;
use Faker\Provider\ar_EG\Text;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Services\StatusService;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\StudentGradeResource\Pages;
use App\Filament\Sms\Resources\StudentGradeResource\RelationManagers;

class StudentGradeResource extends Resource
{
    protected static ?string $model = StudentGrade::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 6;


    public static function form(Form $form): Form
    {
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');

        return $form
            ->schema([
                // Main configuration section 
                Forms\Components\Section::make('Assessment Setup')
                    ->schema([
                        // Grid for main selectors
                        Grid::make(2)->schema([
                            // Academic Period Selection  
                            Forms\Components\Select::make('academic_session_id')
                                ->label('Academic Session')
                                ->options(fn() => AcademicSession::where('school_id', Filament::getTenant()?->id)
                                    ->orderByDesc('start_date')
                                    ->pluck('name', 'id'))
                                ->default(fn() => $currentSession?->id)
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn(Set $set) => $set('term_id', null)),

                            Forms\Components\Select::make('term_id')
                                ->label('Term')
                                ->options(fn(Get $get) => Term::when(
                                    $get('academic_session_id'),
                                    fn($query) => $query->where('academic_session_id', $get('academic_session_id'))
                                )->pluck('name', 'id'))
                                ->default(fn() => $currentTerm?->id)
                                ->required()
                                ->live(),

                            // Class and Subject Selection
                            Forms\Components\Select::make('class_room_id')
                                ->label('Class')
                                ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                                    ->pluck('name', 'id'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn(Set $set) => [
                                    $set('student_ids', []),
                                    $set('grades', [])
                                ]),

                            Forms\Components\Select::make('subject_id')
                                ->label('Subject')
                                ->options(fn() => Subject::where('school_id', Filament::getTenant()->id)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id'))
                                ->required()
                                ->live(),

                            // Student Selection
                            Forms\Components\Select::make('student_ids')
                                ->label('Students')
                                ->multiple()
                                ->options(fn(Get $get) => Student::where('class_room_id', $get('class_room_id'))
                                    ->where('status_id', optional(Status::where('name', 'active')->first())->id)
                                    ->get()
                                    ->mapWithKeys(fn($student) => [
                                        $student->id => optional($student)->admission_number ?
                                            "{$student->full_name} ({$student->admission_number})" :
                                            optional($student)->full_name ?? 'Unknown Student'
                                    ]))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->visible(fn(Get $get) => filled($get('class_room_id')))
                                ->afterStateUpdated(fn(Set $set) => $set('grades', [])),

                            // Assessment Types
                            Forms\Components\Select::make('assessment_type_ids')
                                ->label('Assessment Types')
                                ->multiple()
                                ->options(fn() => AssessmentType::where('school_id', Filament::getTenant()->id)
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn($type) => [
                                        $type->id => "{$type->name} (Max: {$type->max_score})"
                                    ]))
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn(Set $set) => $set('grades', [])),
                        ]),
                    ]),

                // Grades Repeater Section
                Forms\Components\Section::make('Grade Entry')
                    ->schema([
                        Forms\Components\Repeater::make('grades')
                            ->schema([
                                Grid::make(4)->schema([
                                    // Student Name (Disabled)
                                    Forms\Components\Select::make('student_id')
                                        ->label('Student')
                                        ->options(fn(Get $get) => Student::whereIn('id', $get('../../student_ids') ?? [])
                                            ->pluck('full_name', 'id'))
                                        ->disabled()
                                        ->required()
                                        ->columnSpan(1),

                                    // Assessment Type (Disabled) 
                                    Forms\Components\Select::make('assessment_type_id')
                                        ->label('Assessment Type')
                                        ->options(fn(Get $get) => AssessmentType::whereIn('id', $get('../../assessment_type_ids') ?? [])
                                            ->pluck('name', 'id'))
                                        ->disabled()
                                        ->required()
                                        ->columnSpan(1),

                                    // Score Entry
                                    Forms\Components\TextInput::make('score')
                                        ->label('Score')
                                        ->numeric()
                                        ->required()
                                        ->rules([
                                            'required',
                                            'numeric',
                                            'min:0',
                                            function (Get $get, string $attribute, mixed $value) {
                                                $maxScore = optional(AssessmentType::find($get('assessment_type_id')))->max_score ?? 0;
                                                return $value <= $maxScore ? null :
                                                    "Score cannot exceed maximum of {$maxScore}";
                                            },
                                        ])
                                        ->suffix(fn(Get $get) => '/' .
                                            (optional(AssessmentType::find($get('assessment_type_id')))->max_score ?? 'N/A'))
                                        ->columnSpan(1),

                                    // Optional Remarks
                                    Forms\Components\Textarea::make('remarks')
                                        ->label('Remarks')
                                        ->columnSpan(1),
                                ]),
                            ])
                            // Automatically create grade entries for each student + assessment type combination
                            ->defaultItems(function (Get $get) {
                                $students = $get('student_ids') ?? [];
                                // dd($students);
                                $assessmentTypes = $get('assessment_type_ids') ?? [];

                                if (empty($students) || empty($assessmentTypes)) {
                                    return [];
                                }

                                $items = [];
                                foreach ($students as $studentId) {
                                    foreach ($assessmentTypes as $typeId) {
                                        $items[] = [
                                            'student_id' => $studentId,
                                            'assessment_type_id' => $typeId,
                                            'score' => null,
                                            'remarks' => null,
                                        ];
                                    }
                                }
                                return $items;
                            })
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->columnSpanFull()
                            ->itemLabel(fn(array $state): ?string => sprintf(
                                '%s - %s',
                                optional(Student::find($state['student_id'] ?? null))->full_name ?? 'Unknown Student',
                                optional(AssessmentType::find($state['assessment_type_id'] ?? null))->name ?? 'Unknown Type'
                            ))
                            ->visible(
                                fn(Get $get): bool =>
                                filled($get('student_ids')) && filled($get('assessment_type_ids'))
                            ),
                    ]),

                // Hidden Fields
                Forms\Components\Hidden::make('school_id')
                    ->default(fn() => optional(Filament::getTenant())->id),
            ]);
    }


    public static function table(Table $table): Table
    {
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');

        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(['first_name', 'middle_name', 'last_name'])
                    ->formatStateUsing(fn ($state, $record) => $state ?? 'Unknown Student'),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('academicSession.name'),

                TextColumn::make('term.name'),

                TextColumn::make('assessmentType.name')
                    ->label('Assessment Type')
                    ->sortable(),

                // / Score column with proper null handling
                TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record->assessmentType) {
                            return $state ?? 'N/A';
                        }
                        return "{$state}/{$record->assessmentType->max_score}";
                    })
                    ->sortable(),

                TextColumn::make('grade')
                    ->label('Grade')
                    ->formatStateUsing(fn($record) => optional($record?->getGrade())->grade ?? 'N/A'),

                TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unknown')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('graded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Fix student filter to show proper names
                Tables\Filters\SelectFilter::make('student_id')
                    ->multiple()
                    ->searchable()  // Enable searching through students
                    ->label('Student')
                    ->preload()    // Preload options for better performance
                    // Get student options with formatted display
                    ->getSearchResultsUsing(function (string $search): array {
                        return Student::query()
                            ->where('school_id', Filament::getTenant()->id)
                            ->where(function ($query) use ($search) {
                                $query->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereHas('admission', function ($query) use ($search) {
                                        $query->where('admission_number', 'like', "%{$search}%");
                                    });
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($student) {
                                // Format: Student Name - Class (Admission Number)
                                return [
                                    $student->id => $student->full_name . ' - ' .
                                        $student->classRoom->name .
                                        ($student->admission ? ' (' . $student->admission->admission_number . ')' : '')
                                ];
                            })
                            ->toArray();
                    })
                    // Get option label for selected values
                    ->getOptionLabelUsing(function ($value): ?string {
                        $student = Student::query()
                            ->with(['classRoom', 'admission'])
                            ->find($value);

                        if (!$student) return 'Unknown Student';

                        return $student->full_name . ' - ' .
                            optional($student->classRoom)->name .
                            (optional($student->admission)->admission_number ? 
                                ' (' . $student->admission->admission_number . ')' : '');
                    })
                    // Apply the filter to the query
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['values']),
                            fn(Builder $query) => $query->whereIn('student_id', $data['values'])
                        );
                    })
                    // Show active filter indicators
                    ->indicateUsing(function (array $state): array {
                        if (empty($state['values'] ?? [])) {
                            return [];
                        }

                        // Get selected students info
                        $students = Student::whereIn('id', $state['values'])
                            ->get()
                            ->map(function ($student) {
                                return "{$student->full_name} ({$student->classRoom->name})";
                            });

                        return ["Students: " . $students->join(', ')];
                    }),


                Tables\Filters\SelectFilter::make('class_room_id')
                    ->label('Class')
                    ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                        ->pluck('name', 'id'))
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn() => Subject::where('school_id', Filament::getTenant()->id)
                        ->where('is_active', true)
                        ->pluck('name', 'id'))
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assessment_type_id')
                    ->label('Assessment Type')
                    ->options(fn() => AssessmentType::where('school_id', Filament::getTenant()->id)
                        ->pluck('name', 'id'))
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('academic_session_id')
                    ->label('Academic Session')
                    ->options(fn() => AcademicSession::where('school_id', Filament::getTenant()?->id)
                        ->pluck('name', 'id'))
                    ->default(fn() => $currentSession?->id)
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('term_id')
                    ->label('Term')
                    ->options(fn() => Term::where('school_id', Filament::getTenant()?->id)
                        ->pluck('name', 'id'))
                    ->default(fn() => $currentTerm?->id)
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('score_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('score_from')
                                    ->label('Minimum Score')
                                    ->numeric(),
                                Forms\Components\TextInput::make('score_to')
                                    ->label('Maximum Score')
                                    ->numeric(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['score_from'],
                                fn(Builder $query, $score): Builder => $query->where('score', '>=', $score)
                            )
                            ->when(
                                $data['score_to'],
                                fn(Builder $query, $score): Builder => $query->where('score', '<=', $score)
                            );
                    }),

                Tables\Filters\Filter::make('assessment_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('assessment_date', '>=', $date)
                            )
                            ->when(
                                $data['date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('assessment_date', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalWidth('lg')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                // Display Information
                                Placeholder::make('student_name')
                                    ->label('Student')
                                    ->content(fn($record) => optional($record->student)->full_name ?? 'Unknown Student'),

                                Placeholder::make('subject_name')
                                    ->label('Subject')
                                    ->content(fn($record) => optional($record->subject)->name ?? 'Unknown Subject'),

                                Placeholder::make('assessment_type')
                                    ->label('Assessment Type')
                                    ->content(fn($record) => optional($record->assessmentType)->name ?? 'Unknown Type'),

                                Placeholder::make('max_score')
                                    ->label('Maximum Score')
                                    ->content(fn($record) => optional($record->assessmentType)->max_score ?? 'N/A'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('score')
                                    ->required()
                                    ->numeric()
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'min:0',
                                        fn($record) => "max:{$record->assessmentType->max_score}",
                                    ])
                                    ->suffix(fn($record) => "/{$record->assessmentType->max_score}"),

                                DatePicker::make('assessment_date')
                                    ->required()
                                    ->maxDate(now()),

                                Textarea::make('remarks')
                                    ->maxLength(255)
                                    ->columnSpan(3),
                            ]),
                    ])
                    ->fillForm(function (StudentGrade $record): array {
                        // Load existing grade data
                        return [
                            'score' => $record->score,
                            'assessment_date' => $record->assessment_date,
                            'remarks' => $record->remarks,
                        ];
                    })
                    ->action(function (StudentGrade $record, array $data): void {
                        $data['modified_by'] = auth()->id();
                        $record->update($data);
                        
                        Notification::make()
                            ->success()
                            ->title('Grade Updated')
                            ->body('The grade has been updated successfully.')
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentGrades::route('/'),
            'create' => Pages\CreateStudentGrade::route('/create'),
            'edit' => Pages\EditStudentGrade::route('/{record}/edit'),
        ];
    }
}
