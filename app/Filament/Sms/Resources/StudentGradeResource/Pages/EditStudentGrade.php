<?php

namespace App\Filament\Sms\Resources\StudentGradeResource\Pages;

use Filament\Actions;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\StudentGrade;
use App\Models\AssessmentType;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\StudentGradeResource;
use Illuminate\Database\Eloquent\Model;

class EditStudentGrade extends EditRecord
{
    protected static string $resource = StudentGradeResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Grade Information')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            // Display Information
                            Placeholder::make('student_name')
                                ->label('Student')
                                ->content(fn($record) => $record->student->full_name),

                            Placeholder::make('subject_name')
                                ->label('Subject')
                                ->content(fn($record) => $record->subject->name),

                            Placeholder::make('assessment_type')
                                ->label('Assessment Type')
                                ->content(fn($record) => $record->assessmentType->name),

                            Placeholder::make('max_score')
                                ->label('Maximum Score')
                                ->content(fn($record) => $record->assessmentType->max_score),
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
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['modified_by'] = auth()->id();
        $record->update($data);
        return $record;
    }
}