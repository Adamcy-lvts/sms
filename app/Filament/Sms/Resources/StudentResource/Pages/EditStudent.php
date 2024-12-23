<?php

namespace App\Filament\Sms\Resources\StudentResource\Pages;

use App\Filament\Sms\Resources\StudentResource;
use App\Models\Admission;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // If the student has an associated admission, fill in the admission data
        if ($this->record->admission) {
            $admissionData = $this->record->admission->toArray();
            $data = array_merge($data, $admissionData);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $school = Filament::getTenant();

        if ($school->hasFeature('Admission Management')) {
            // Update only student data
            $record->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'status_id' => $data['status_id'],
                'phone_number' => $data['phone_number'] ?? null,
                'class_room_id' => $data['class_room_id'],
                'profile_picture' => $data['profile_picture'] ?? null,
                'admission_number' => $data['admission_number'] ?? null,
            ]);
        } else {
            // Update both student and admission data
            if ($record->admission) {
                $record->admission->update([
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
                ]);
            } else {
                // If no admission record exists, create one
                $admission = Admission::create([
                    'school_id' => $school->id,
                    // ... add all admission fields here ...
                ]);
                $record->admission_id = $admission->id;
            }

            $record->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'status_id' => $data['status_id'],
                'phone_number' => $data['phone_number'] ?? null,
                'class_room_id' => $data['class_room_id'],
                'profile_picture' => $data['profile_picture'] ?? null,
                'admission_number' => $data['admission_number'] ?? null,
            ]);
        }

        return $record;
    }
}