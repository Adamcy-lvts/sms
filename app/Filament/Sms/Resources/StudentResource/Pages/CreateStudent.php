<?php

namespace App\Filament\Sms\Resources\StudentResource\Pages;

use Filament\Actions;
use App\Models\Admission;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\StudentResource;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    public $admission;

    public function mount(): void
    {

        // Validate the 'record' query parameter
        $record = request()->query('record');

        // $admissionExist = Admission::where('id', $record)->exists();

        // if (!$admissionExist) {
        //     abort(404);
        // }

        // Find the Admission record using the provided ID
        $this->admission = Admission::find($record);

        // If an this->Admission record is found, pre-fill the form fields
        if ($this->admission) {
            $this->form->fill([

                'first_name' => $this->admission->first_name,
                'last_name' => $this->admission->last_name,
                'middle_name' => $this->admission->middle_name,
                'date_of_birth' => $this->admission->date_of_birth,
                // 'class_room_id' => $this->admission->class_room_id,
                'phone_number' => $this->admission->phone_number,
                'address' => $this->admission->address,
                'admitted_date' => $this->admission->admitted_date,
                // 'addmission_id' => $this->admission->id,
                'profile_picture' => $this->admission->passport_photograph,
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Create Student record
        $studentData =  [

            'profile_picture' => $data['profile_picture'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'],
            'date_of_birth' => $data['date_of_birth'],
            'status_id' => $data['status_id'],
            'phone_number' => $data['phone_number'],
            'admitted_date' => $this->admission->admitted_date,
            'class_room_id' => $data['class_room_id'],
            'admission_id' => $this->admission->id,
            'user_id' => 1,
        ];

        // dd($studentData);

        $record = new ($this->getModel())($studentData);

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
