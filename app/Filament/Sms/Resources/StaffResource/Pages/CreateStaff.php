<?php

namespace App\Filament\Sms\Resources\StaffResource\Pages;

use App\Models\User;
use App\Models\Staff;
use Filament\Actions;
use App\Models\Teacher;
use Illuminate\Support\Str;
use App\Models\Qualification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\StaffResource;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;


    protected function handleRecordCreation(array $data): Model
    {
        // Check if staff already exists
        $existingStaff = Staff::where('email', $data['email'])
            ->orWhere('employee_id', $data['employee_id'])
            ->first();

        if ($existingStaff) {
            Notification::make()
                ->title('Staff Already Exists')
                ->success()
                ->body('Staff with the email or employee ID already exists. Please check and try again.')
                ->send();
            // $this->redirectRoute('filament.sms.resources.staff.index', ['tenant' => Filament::getTenant()]);
            return $existingStaff;
        }

        $tenant = Filament::getTenant();

        // dd($tenant);
        // Create User record if the flag is set
        if ($data['create_user'] ?? false) {

            $userExist = User::where('email', $data['email'])->first();
            if (!$userExist) {
                $user = new User([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'middle_name' => $data['middle_name'],
                    // 'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                    'status_id' => $data['status_id'] ?? null,
                    'email' => $data['email'],
                    'password' => Hash::make($data['phone_number']),
                ]);

                $user->save();

                if ($tenant) {
                    $tenant->members()->attach($user);
                }

                $data['user_id'] = $user->id;
            }
        }

        // Create Staff record
        $staffRecord = [

            'user_id' => $data['user_id'] ?? null,
            'designation_id' => $data['designation_id'],
            'employee_id' => $data['employee_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'address' => $data['address'],
            'hire_date' => $data['hire_date'],
            'status_id' => $data['status_id'],
            'salary' => $data['salary'],
            'bank_id' => $data['bank_id'],
            'account_number' => $data['account_number'],
            'profile_picture' => $data['profile_picture'],
            'qualifications' => $data['qualifications'],
            'emergency_contact' => $data['emergency_contact'],
        ];

        $record = new ($this->getModel())($staffRecord);

        if (static::getResource()::isScopedToTenant() && $tenant) {
            $record = $this->associateRecordWithTenant($record, $tenant);
        }

        $record->save();

        // Create Qualification record with JSON data
        if (!empty($data['qualifications'])) {
            $qualification = new Qualification([
                'staff_id' => $record->id,
                'qualifications' => $data['qualifications'], // Store as JSON
            ]);

            if ($tenant) {
                $qualification = $this->associateRecordWithTenant($qualification, $tenant);
            }

            $qualification->save();
        }


        // Create Teacher record if the flag is set
        if ($data['is_teacher'] ?? false) {
            $teacher = new Teacher([
                'staff_id' => $record->id,
                'specialization' => $data['specialization'] ?? null,
                'teaching_experience' => $data['teaching_experience'] ?? null,
            ]);

            if ($tenant) {
                $teacher = $this->associateRecordWithTenant($teacher, $tenant);
            }

            $teacher->save();
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
