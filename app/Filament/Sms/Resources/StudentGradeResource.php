<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Student;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StudentGrade;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\StudentGradeResource\Pages;
use App\Filament\Sms\Resources\StudentGradeResource\RelationManagers;
use Faker\Provider\ar_EG\Text;

class StudentGradeResource extends Resource
{
    protected static ?string $model = StudentGrade::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assessment Information')
                ->schema([
                    Select::make('subject_assessment_id')
                        ->relationship('assessment', 'title')
                        ->preload()
                        // ->native(false)
                        // ->searchable()
                        ->required()
                        ->disabled(fn($context) => $context === 'edit')
                        ->afterStateHydrated(function ($component, $state, ?StudentGrade $record) {
                            if ($record && $record->assessment) {
                                $assessment = $record->assessment;
                                $component->helperText(new HtmlString("
                                    <div class='text-sm'>
                                        <p>Subject: {$assessment->subject->name}</p>
                                        <p>Class: {$assessment->classRoom->name}</p>
                                        <p>Type: {$assessment->assessmentType->name}</p>
                                        <p>Maximum Score: {$assessment->assessmentType->max_score}</p>
                                    </div>
                                "));
                            }
                        })
                        ->live(),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Placeholder::make('assessment_details')
                                ->content(function ($get) {
                                    $assessmentId = $get('subject_assessment_id');
                                    if (!$assessmentId) return '';

                                    $assessment = \App\Models\SubjectAssessment::find($assessmentId);
                                    if (!$assessment) return '';

                                    return new HtmlString("
                                        <div class='text-sm space-y-1'>
                                            <p><strong>Subject:</strong> {$assessment->subject->name}</p>
                                            <p><strong>Class:</strong> {$assessment->classRoom->name}</p>
                                            <p><strong>Type:</strong> {$assessment->assessmentType->name}</p>
                                            <p><strong>Maximum Score:</strong> {$assessment->assessmentType->max_score}</p>
                                        </div>
                                    ");
                                })
                        ])
                ])->columnSpanFull(),

            Forms\Components\Section::make('Student Grades')
                ->schema([
                    Repeater::make('grades')
                        ->schema([
                            Select::make('student_id')
                                ->label('Student')
                                ->options(function ($get) {
                                    $assessmentId = $get('../../subject_assessment_id');
                                    if (!$assessmentId) return [];

                                    $assessment = \App\Models\SubjectAssessment::find($assessmentId);
                                    if (!$assessment) return [];

                                    return Student::where('class_room_id', $assessment->class_room_id)
                                        ->get()
                                        ->mapWithKeys(fn($student) => [$student->id => $student->full_name]);
                                })
                                ->required()
                                ->searchable(),

                            TextInput::make('score')
                                ->numeric()
                                ->required()
                                ->rules([
                                    'numeric',
                                    'min:0',
                                    function ($get) {
                                        return function ($attribute, $value, $fail) use ($get) {
                                            $assessmentId = $get('../../subject_assessment_id');
                                            $assessment = \App\Models\SubjectAssessment::find($assessmentId);
                                            if ($assessment && $value > $assessment->assessmentType->max_score) {
                                                $fail("Score cannot exceed maximum score of {$assessment->assessmentType->max_score}");
                                            }
                                        };
                                    },
                                ]),

                            Forms\Components\Textarea::make('remarks')
                                ->rows(2)
                                ->maxLength(255),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add Student Grade')
                        ->reorderableWithButtons()
                        ->deleteAction(
                            fn(Forms\Components\Actions\Action $action) => $action->requiresConfirmation()
                        )
                ])->columnSpanFull()
                ->visible(fn($get) => filled($get('subject_assessment_id'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(['first_name', 'middle_name', 'last_name']),

                TextColumn::make('assessment.subject.name')
                    ->label('Subject')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('assessment.classRoom.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('assessment.academicSession.name'),

                TextColumn::make('assessment.term.name'),

                TextColumn::make('assessment.assessmentType.name')
                    ->label('Assessment Type')
                    ->sortable(),

                TextColumn::make('score')
                    ->sortable()
                    ->formatStateUsing(fn($record) =>
                    "{$record->score}/{$record->assessment->assessmentType->max_score}"),

                TextColumn::make('grade')
                    ->label('Grade')
                    ->formatStateUsing(fn($record) => optional($record->getGrade())->grade),

                TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('graded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_assessment_id')
                    ->relationship('assessment', 'title')
                    ->label('Assessment')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assessment.subject_id')
                    ->relationship('assessment.subject', 'name')
                    ->label('Subject')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assessment.class_room_id')
                    ->relationship('assessment.classRoom', 'name')
                    ->label('Class')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
