<?php

namespace App\Filament\Sms\Resources\StudentGradeResource\Pages;

use Filament\Actions;
use Filament\Forms\Form;
use App\Models\StudentGrade;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\StudentGradeResource;
use Illuminate\Database\Eloquent\Model;

class EditStudentGrade extends EditRecord
{
    protected static string $resource = StudentGradeResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);
        
        return $record;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Assessment Information')
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('subject_name')
                            ->label('Subject'),
                        Placeholder::make('student_name')
                            ->label('Student'),
                        Placeholder::make('assessment_type')
                            ->label('Assessment Type'),
                        Placeholder::make('max_score')
                            ->label('Maximum Score'),
                    ]),

                    Grid::make()->schema([
                        TextInput::make('score')
                            ->required()
                            ->numeric()
                            ->rules([
                                'numeric',
                                'min:0',
                                fn($record) => "max:{$record->assessment->assessmentType->max_score}",
                            ]),

                        TextInput::make('remarks')
                            ->maxLength(255),
                    ])->columns(2),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array 
    {
        $data['student_name'] = $this->record->student->full_name;
        $data['subject_name'] = $this->record->assessment->subject->name;
        $data['assessment_type'] = $this->record->assessment->assessmentType->name;
        $data['max_score'] = $this->record->assessment->assessmentType->max_score;
        
        return $data;
    }
}