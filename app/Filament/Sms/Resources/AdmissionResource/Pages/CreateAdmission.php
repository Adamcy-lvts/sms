<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use Filament\Actions;
use App\Models\Admission;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\AdmissionResource;
use App\Models\AcademicSession;

class CreateAdmission extends CreateRecord
{
    protected static string $resource = AdmissionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $session = AcademicSession::find($data['academic_session_id']);

        $sessionName = $session->name ?? null;
        // Create Admission record
        $admissionData =  [

            'academic_session_id' => $data['academic_session_id'],
            'session' => $sessionName,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'],
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
            'passport_photograph' => $data['passport_photograph'] ?? null,

        ];

        $record = new ($this->getModel())($admissionData);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            return $this->associateRecordWithTenant($record, $tenant);
        }

        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
