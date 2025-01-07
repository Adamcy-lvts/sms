<?php

namespace App\Filament\Sms\Resources\StaffResource\Pages;

use App\Models\User;
use App\Models\Staff;
use Filament\Actions;
use App\Models\Teacher;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\StaffResource;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    // Handle form data before saving updates
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure school_id is set
        $data['school_id'] = Filament::getTenant()->id;
        return $data;
    }



    // Update the form mount method to ensure data is available
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        // Initialize empty data array
        $data = [];

        // Load base staff data safely
        $data = $this->record->toArray();

        // Safely load user data
        if ($user = $this->record->user) {
            $data['create_user_account'] = true;

            // Load roles with null safety
            try {
                $data['roles'] = $user->roles()
                    ->where('school_id', Filament::getTenant()->id)
                    ->pluck('id')
                    ->all();
            } catch (\Exception $e) {
                $data['roles'] = [];
                Log::warning('Failed to load roles', ['error' => $e->getMessage()]);
            }

            // Load permissions with null safety
            try {
                $data['permissions'] = $user->permissions()
                    ->pluck('id')
                    ->all();
            } catch (\Exception $e) {
                $data['permissions'] = [];
                Log::warning('Failed to load permissions', ['error' => $e->getMessage()]);
            }
        } else {
            $data['create_user_account'] = false;
            $data['roles'] = [];
            $data['permissions'] = [];
        }

        // Load teacher data safely
        if ($teacher = $this->record->teacher) {
            $data['is_teacher'] = true;
            $data['teacher'] = [
                'specialization' => $teacher->specialization,
                'teaching_experience' => $teacher->teaching_experience,
                'subjects' => $teacher->subjects()->pluck('id')->all() ?? [],
                'class_rooms' => $teacher->classRooms()->pluck('id')->all() ?? [],
            ];
        } else {
            $data['is_teacher'] = false;
            $data['teacher'] = [
                'subjects' => [],
                'class_rooms' => [],
            ];
        }

        // Load qualifications safely
        if ($qualification = $this->record->qualifications()->first()) {
            $data['qualifications'] = collect($qualification->qualifications ?? [])
                ->map(fn($qual) => [
                    'name' => $qual['name'] ?? '',
                    'institution' => $qual['institution'] ?? '',
                    'year_obtained' => $qual['year_obtained'] ?? null,
                    'documents' => $qual['documents'] ?? null,
                ])
                ->all();
        } else {
            $data['qualifications'] = [];
        }

        // Set default values for other fields
        $data['default_password_type'] = 'custom';
        $data['send_credentials'] = false;
        $data['force_password_change'] = false;

        try {
            $this->form->fill($data);
        } catch (\Exception $e) {
            Log::error('Form fill error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            Notification::make()
                ->warning()
                ->title('Data Loading Issue')
                ->body('Some data could not be loaded properly. Please check the form.')
                ->send();
        }
    }
    // Main method to handle record updates
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            try {
                // Update basic staff information
                $staffData = collect($data)->except([
                    'qualifications',
                    'teacher',
                    'is_teacher',
                    'roles',
                    'permissions',
                ])->toArray();

                $record->update($this->mutateFormDataBeforeSave($staffData));

                // Handle qualifications update
                if (isset($data['qualifications'])) {
                    $this->updateQualifications($data['qualifications']);
                }

                // Handle teacher information
                $this->updateTeacherInformation($data);

                // Handle user account and role updates
                if ($record->user_id) {
                    $this->updateUserAccount($data);
                }

                Notification::make()
                    ->success()
                    ->title('Staff Updated')
                    ->body('Staff record updated successfully')
                    ->send();

                return $record->fresh(); // Return refreshed model with updated relationships

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Staff update failed', [
                    'error' => $e->getMessage(),
                    'staff_id' => $record->id
                ]);

                throw $e;
            }
        });
    }

    // Update qualifications
    protected function updateQualifications(array $qualifications): void
    {
        // Format qualifications data
        $formattedQualifications = collect($qualifications)->map(function ($qual) {
            return [
                'name' => $qual['name'],
                'institution' => $qual['institution'],
                'year_obtained' => $qual['year_obtained'],
                'documents' => $qual['documents'] ?? null,
            ];
        })->toArray();

        // Update or create qualifications record
        $this->record->qualifications()->updateOrCreate(
            ['staff_id' => $this->record->id],
            [
                'school_id' => Filament::getTenant()->id,
                'qualifications' => $formattedQualifications
            ]
        );
    }

    // Handle teacher-specific updates
    protected function updateTeacherInformation(array $data): void
    {
        if ($data['is_teacher'] ?? false) {
            // Update or create teacher record
            $teacher = Teacher::updateOrCreate(
                ['staff_id' => $this->record->id],
                [
                    'school_id' => Filament::getTenant()->id,
                    'specialization' => $data['teacher']['specialization'] ?? null,
                    'teaching_experience' => $data['teacher']['teaching_experience'] ?? null,
                ]
            );

            // Sync relationships
            if (isset($data['teacher']['subjects'])) {
                $teacher->subjects()->sync($data['teacher']['subjects']);
            }
            if (isset($data['teacher']['class_rooms'])) {
                $teacher->classRooms()->sync($data['teacher']['class_rooms']);
            }
        } else {
            // Remove teacher record if staff is no longer a teacher
            Teacher::where('staff_id', $this->record->id)->delete();
        }
    }

    // Update user account and roles
    protected function updateUserAccount(array $data): void
    {
        $user = User::find($this->record->user_id);
        if (!$user) return;

        // Update roles and permissions
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }
        if (isset($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }
    }

    // Add useful header actions
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Cleanup related records before deletion
                    if ($this->record->user_id) {
                        User::find($this->record->user_id)?->delete();
                    }
                }),

            Actions\Action::make('resetPassword')
                ->visible(fn() => $this->record->user_id !== null)
                ->action(function () {
                    // Handle password reset logic
                    $user = User::find($this->record->user_id);
                    if ($user) {
                        $user->update([
                            'password' => Hash::make($this->record->phone_number)
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Password Reset')
                            ->body('Password has been reset to phone number')
                            ->send();
                    }
                }),
        ];
    }
}
