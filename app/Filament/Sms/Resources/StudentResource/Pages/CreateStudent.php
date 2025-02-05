<?php

namespace App\Filament\Sms\Resources\StudentResource\Pages;

use Filament\Actions;

use App\Models\Student;
use App\Models\Admission;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Services\FeatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FeatureCheckResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;
use App\Services\AdmissionNumberGenerator;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\StudentResource;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    public ?Admission $admission = null;

    protected ?FeatureCheckResult $limitCheckResult = null;

    public function mount(): void
    {
        $recordId = request()->query('record');

        if ($recordId) {
            try {
                $this->admission = Admission::findOrFail($recordId);
                $this->fillFormFromAdmission();
            } catch (\Exception $e) {
                Log::error("Error loading admission record: " . $e->getMessage());
                Notification::make()
                    ->title('Error')
                    ->body('Failed to load admission record.')
                    ->danger()
                    ->send();
            }
        } else {
            // Generate admission number for new students
            $generator = new AdmissionNumberGenerator();
            $admissionNumber = $generator->generate();
            
            // Pre-fill the form with the generated number
            $this->form->fill([
                'admission_number' => $admissionNumber
            ]);
        }

        if ($recordId) {
            try {
                $this->admission = Admission::findOrFail($recordId);
                $this->fillFormFromAdmission();
            } catch (\Exception $e) {
                Log::error("Error loading admission record: " . $e->getMessage());
                Notification::make()
                    ->title('Error')
                    ->body('Failed to load admission record.')
                    ->danger()
                    ->send();
            }
        }

        parent::mount();
    }

    protected function fillFormFromAdmission(): void
    {
        $this->form->fill([
            'first_name' => $this->admission->first_name,
            'last_name' => $this->admission->last_name,
            'middle_name' => $this->admission->middle_name,
            'date_of_birth' => $this->admission->date_of_birth,
            'phone_number' => $this->admission->phone_number,
            'address' => $this->admission->address,
            'admitted_date' => $this->admission->admitted_date,
            'profile_picture' => $this->admission->passport_photograph,
            // Add other fields as necessary
        ]);
    }

    protected function beforeCreate(): void
    {
        $school = Filament::getTenant();
        $featureService = app(FeatureService::class);

        // Pre-creation limit check
        $preCheckResult = $featureService->checkResourceLimit($school, 'students');
        if (!$preCheckResult->allowed) {
            Notification::make()
                ->title('Student Limit Reached')
                ->body($preCheckResult->message)
                ->danger()
                ->persistent()
                ->send();
            
            $this->halt();
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $school = Filament::getTenant();

        DB::beginTransaction();

        try {
            if (empty($data['admission_number'])) {
                $generator = new AdmissionNumberGenerator();
                $data['admission_number'] = $generator->generate();
            }

            $record = $school->hasFeature('Admission Management') 
                ? $this->createSimpleStudentRecord($data, $school)
                : $this->createFullStudentRecord($data, $school);

            DB::commit();
            return $record;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating student record: " . $e->getMessage());
            throw $e;
        }
    }

    protected function afterCreate(): void
    {
        $school = Filament::getTenant();
        $featureService = app(FeatureService::class);

        // Post-creation limit check
        $postCheckResult = $featureService->checkResourceLimit($school, 'students');
        $this->limitCheckResult = $postCheckResult;

        if ($postCheckResult->status === 'warning') {
            Notification::make()
                ->title('Student Limit Warning')
                ->body($postCheckResult->message)
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function createSimpleStudentRecord(array $data, $school): Model
    {
        $studentData = [
            'school_id' => $school->id,
            'profile_picture' => $data['profile_picture'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'date_of_birth' => $data['date_of_birth'],
            'status_id' => $data['status_id'],
            'phone_number' => $data['phone_number'] ?? null,
            'class_room_id' => $data['class_room_id'],
            'admission_id' => $this->admission?->id,
            'user_id' => auth()->id()
        ];

        $record = new ($this->getModel())($studentData);

        if (static::getResource()::isScopedToTenant()) {
            $record = $this->associateRecordWithTenant($record, $school);
        }

        $record->save();

        return $record;
    }

    protected function createFullStudentRecord(array $data, $school): Model
    {
        $session = AcademicSession::findOrFail($data['academic_session_id']);
        $admission = $this->createAdmissionRecord($data, $school, $session);

        $studentData = [
            'school_id' => $school->id,
            'admission_id' => $admission->id,
            'class_room_id' => $data['class_room_id'],
            'status_id' => $data['status_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'date_of_birth' => $data['date_of_birth'],
            'phone_number' => $data['phone_number'] ?? null,
            'profile_picture' => $data['profile_picture'] ?? null,
            'admission_number' => $data['admission_number'] ?? null,
            'user_id' => null,
            'created_by' => auth()->id()
        ];

        $record = new Student($studentData);

        if (static::getResource()::isScopedToTenant()) {
            $record = $this->associateRecordWithTenant($record, $school);
        }

        $record->save();

        return $record;
    }

    protected function createAdmissionRecord(array $data, $school, $session): Admission
    {
            // Create both Admission and Student records
            $session = AcademicSession::find($data['academic_session_id']);
            $sessionName = $session->name ?? null;
        $admissionData = [
           'school_id' => $school->id,
                'academic_session_id' => $data['academic_session_id'],
                'session' => $sessionName,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'address' => $data['address'],
                'phone_number' => $data['phone_number'],
                'email' => $data['email'],
                'state_id' => $data['state_id'],
                'lga_id' => $data['lga_id'],
                'religion' => $data['religion'],
                'blood_group' => $data['blood_group'],
                'genotype' => $data['genotype'],
                'previous_school_name' => $data['previous_school_name'],
                'previous_class' => $data['previous_class'],
                'admitted_date' => $data['admitted_date'],
                'application_date' => $data['application_date'],
                'admission_number' => $data['admission_number'],
                'status_id' => $data['status_id'],
                'guardian_name' => $data['guardian_name'],
                'guardian_relationship' => $data['guardian_relationship'],
                'guardian_phone_number' => $data['guardian_phone_number'],
                'guardian_email' => $data['guardian_email'],
                'guardian_address' => $data['guardian_address'],
                'emergency_contact_name' => $data['emergency_contact_name'],
                'emergency_contact_relationship' => $data['emergency_contact_relationship'],
                'emergency_contact_phone_number' => $data['emergency_contact_phone_number'],
                'emergency_contact_email' => $data['emergency_contact_email'],
                'disability_type' => $data['disability_type'] ?? null,
                'disability_description' => $data['disability_description'] ?? null,
                'passport_photograph' => $data['profile_picture'] ?? null,
        ];

        $admission = new Admission($admissionData);

        if (static::getResource()::isScopedToTenant()) {
            $admission = $this->associateRecordWithTenant($admission, $school);
        }

        $admission->save();

        return $admission;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        // Use the stored limit check result
        if ($this->limitCheckResult && $this->limitCheckResult->remaining <= 5 && $this->limitCheckResult->remaining > 0) {
            return "Student created successfully ({$this->limitCheckResult->remaining} slot" . 
                   ($this->limitCheckResult->remaining === 1 ? '' : 's') . " remaining)";
        }

        return "Student created successfully";
    }
}