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

    public function mount(): void
    {
        $this->authorizeAccess();

        // Check if assessment ID is provided in the URL
        if ($assessmentId = request()->query('assessment')) {
            try {
                $assessment = SubjectAssessment::findOrFail($assessmentId);
                $this->form->fill(['subject_assessment_id' => $assessment->id]);
            } catch (\Exception $e) {
                Notification::make()
                    ->danger()
                    ->title('Invalid Assessment')
                    ->body('The specified assessment could not be found.')
                    ->send();
            }
        }
    }

    protected function authorizeAccess(): void
    {
        static::authorizeResourceAccess();

        if (! auth()->user()) {
            throw new AuthorizationException();
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Validate assessment exists and is not published
            $assessment = SubjectAssessment::findOrFail($data['subject_assessment_id']);

            if ($assessment->is_published) {
                throw ValidationException::withMessages([
                    'subject_assessment_id' => 'Cannot add grades to a published assessment.',
                ]);
            }

            // Check for duplicate student entries
            $studentIds = collect($data['grades'])->pluck('student_id')->toArray();
            if (count($studentIds) !== count(array_unique($studentIds))) {
                throw ValidationException::withMessages([
                    'grades' => 'Duplicate student entries detected.',
                ]);
            }

            // Check for existing grades
            $existingGrades = StudentGrade::where('subject_assessment_id', $assessment->id)
                ->whereIn('student_id', $studentIds)
                ->exists();

            if ($existingGrades) {
                throw ValidationException::withMessages([
                    'grades' => 'Some students already have grades for this assessment.',
                ]);
            }

            // Begin transaction
            return DB::transaction(function () use ($data, $assessment) {
                $gradesCreated = [];
                $firstGrade = null;

                foreach ($data['grades'] as $grade) {
                    $studentGrade = StudentGrade::create([
                        'subject_assessment_id' => $data['subject_assessment_id'],
                        'student_id' => $grade['student_id'],
                        'score' => $grade['score'],
                        'remarks' => $grade['remarks'] ?? null,
                        'school_id' => $assessment->school_id,
                        'recorded_by' => auth()->id(),
                        'graded_at' => now(),
                    ]);

                    $gradesCreated[] = $studentGrade;

                    // Store the first grade to return
                    if (!$firstGrade) {
                        $firstGrade = $studentGrade;
                    }
                }

                // Send success notification with details
                Notification::make()
                    ->success()
                    ->title('Grades Recorded Successfully')
                    ->body(sprintf(
                        'Recorded %d grades for %s - %s',
                        count($gradesCreated),
                        $assessment->subject->name,
                        $assessment->title
                    ))
                    ->actions([
                        Action::make('view_grades')
                            ->label('View Grades')
                            ->url(static::getResource()::getUrl('index', [
                                'tableFilters' => [
                                    'subject_assessment_id' => $assessment->id
                                ]
                            ]))
                            ->button(),
                    ])
                    ->persistent()
                    ->send();

                // Return the first grade instead of the assessment
                return $firstGrade;
            });
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Validation Error')
                ->body($e->getMessage())
                ->send();
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error Recording Grades')
                ->body('An unexpected error occurred while recording grades.')
                ->send();

            report($e); // Log the error
            throw $e;
        }
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
