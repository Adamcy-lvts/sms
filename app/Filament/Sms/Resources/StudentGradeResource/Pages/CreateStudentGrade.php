<?php

namespace App\Filament\Sms\Resources\StudentGradeResource\Pages;

use Filament\Actions;
use Filament\Forms\Set;
use App\Models\StudentGrade;
use App\Models\SubjectAssessment;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Filament\Sms\Resources\StudentGradeResource;

class CreateStudentGrade extends CreateRecord
{
    protected static string $resource = StudentGradeResource::class;


    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $createdGrades = [];

            foreach ($data['grade_entries'] as $entry) {
                foreach ($entry['scores'] as $score) {
                    $grade = StudentGrade::create([
                        'school_id' => $data['school_id'],
                        'student_id' => $entry['student_id'],
                        'subject_id' => $data['subject_id'],
                        'class_room_id' => $data['class_room_id'],
                        'assessment_type_id' => $score['assessment_type_id'],
                        'academic_session_id' => $data['academic_session_id'],
                        'term_id' => $data['term_id'],
                        'score' => $score['score'],
                        'remarks' => $entry['remarks'] ?? null,
                        'recorded_by' => auth()->id(),
                        'assessment_date' => $data['assessment_date'],
                        'graded_at' => now(),
                    ]);

                    $createdGrades[] = $grade;
                }
            }

            if (empty($createdGrades)) {
                throw ValidationException::withMessages([
                    'grade_entries' => 'At least one grade must be entered.',
                ]);
            }

            Notification::make()
                ->success()
                ->title('Grades Recorded Successfully')
                ->body(count($createdGrades) . ' grades have been recorded.')
                ->send();

            return $createdGrades[0];
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // We're using our custom notification instead
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate and clean up grade data
        if (isset($data['grades'])) {
            foreach ($data['grades'] as $key => $grade) {
                // Remove any empty or invalid entries
                if (empty($grade['student_id']) || !isset($grade['score'])) {
                    unset($data['grades'][$key]);
                    continue;
                }

                // Ensure score is properly formatted
                $data['grades'][$key]['score'] = (float) $grade['score'];
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Record Grades'),
            $this->getCancelFormAction(),
        ];
    }
}
