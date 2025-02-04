<?php

namespace App\Filament\Sms\Resources\StaffResource\Pages;

use App\Models\User;
use App\Models\Staff;
use App\Models\School;
use App\Models\Status;
use App\Models\Teacher;
use Filament\Facades\Filament;
use App\Services\FeatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FeatureCheckResult;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\EmployeeIdGenerator;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\StaffResource;
use Illuminate\Validation\ValidationException;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_id'] = Filament::getTenant()->id;
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            try {
                $school = Filament::getTenant();
                $featureService = app(FeatureService::class);

                // Generate Employee ID if needed
                if (empty($data['employee_id'])) {
                    try {
                        $data['employee_id'] = app(EmployeeIdGenerator::class);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->danger()
                            ->title('Error Generating Employee ID')
                            ->body($e->getMessage())
                            ->send();
                        throw $e;
                    }
                }

                // Prepare staff data
                $staffData = collect($data)->except([
                    'qualifications',
                    'teacher',
                    'is_teacher',
                    'create_user_account',
                    'roles',
                    'permissions',
                    'default_password_type',
                    'custom_password',
                    'send_credentials',
                    'force_password_change',
                    'id_generation_type'
                ])->toArray();

                // Create staff record
                try {
                    $staff = Staff::create($this->mutateFormDataBeforeCreate($staffData));
                } catch (\Exception $e) {
                    DB::rollBack();
                    Notification::make()
                        ->danger()
                        ->title('Error Creating Staff Record')
                        ->body('Failed to create basic staff record: ' . $e->getMessage())
                        ->send();
                    throw $e;
                }
                // dd($data);
                // Handle qualifications if present
                if (!empty($data['qualifications'])) {
                    // Format qualifications to match your JSON structure
                    $qualificationsData = collect($data['qualifications'])->map(function ($qualification) {
                        return [
                            'name' => $qualification['name'],
                            'institution' => $qualification['institution'],
                            'year_obtained' => $qualification['year_obtained'],
                            'documents' => $qualification['documents'] ?? null,
                        ];
                    })->toArray();

                    // Create qualification record with JSON data
                    $staff->qualifications()->create([
                        'school_id' => Filament::getTenant()->id,
                        'qualifications' => $qualificationsData
                    ]);
                }

                // Handle teacher data
                if ($data['is_teacher'] ?? false) {
                    try {
                        // Create teacher record
                        $teacher = Teacher::create([
                            'staff_id' => $staff->id,
                            'school_id' => Filament::getTenant()->id,
                            'specialization' => $data['teacher']['specialization'] ?? null,
                            'teaching_experience' => $data['teacher']['teaching_experience'] ?? null,
                        ]);

                        // Sync relationships
                        if (!empty($data['teacher']['subjects'])) {
                            $teacher->subjects()->sync($data['teacher']['subjects']);
                        }
                        if (!empty($data['teacher']['class_rooms'])) {
                            $teacher->classRooms()->sync($data['teacher']['class_rooms']);
                        }
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->danger()
                            ->title('Error Creating Teacher Record')
                            ->body('Failed to create teacher record: ' . $e->getMessage())
                            ->send();
                        throw $e;
                    }
                }

                // Handle user account creation
                if ($data['create_user_account'] ?? false) {
                    // Check staff user account limit before creating user
                    $userResult = $featureService->checkResourceLimit($school, 'staff_users');
                    
                    if (!$userResult->allowed) {
                        $this->sendStaffUserLimitReachedNotification($school);
                        
                        // Still create staff record but without user account
                        Notification::make()
                            ->warning()
                            ->title('Staff Created Without User Account')
                            ->body('Staff record created but user account could not be created due to plan limits.')
                            ->persistent()
                            ->send();
                    } else {
                        // Create user account if within limits
                        $password = $this->generatePassword($data);
                        $user = $this->createUserAccount($staff, $data, $password);
                        $staff->user_id = $user->id;
                        $staff->save();

                        if ($data['send_credentials'] ?? false) {
                            try {
                                $this->sendLoginCredentials($staff, $password);
                            } catch (\Exception $e) {
                                // Don't rollback for email failures, just notify
                                Notification::make()
                                    ->warning()
                                    ->title('Warning')
                                    ->body('Staff created successfully but failed to send credentials: ' . $e->getMessage())
                                    ->send();
                            }
                        }
                    }
                }

                // After successful staff creation, check remaining slots
                $postCreationResult = $featureService->checkResourceLimit($school, 'staff');
                if ($postCreationResult->status === 'warning') {
                    $this->sendStaffLimitWarningNotification($school, $postCreationResult);
                }

                // If we get here, everything succeeded
                Notification::make()
                    ->success()
                    ->title('Success')
                    ->body(
                        $data['create_user_account']
                            ? "Staff record and user account created successfully."
                            : "Staff record created successfully."
                    )
                    ->send();

                // Log the successful creation
                // activity()
                //     ->performedOn($staff)
                //     ->causedBy(auth()->user())
                //     ->withProperties([
                //         'school_id' => Filament::getTenant()->id,
                //         'action' => 'created',
                //         'has_user_account' => $data['create_user_account'] ?? false,
                //         'is_teacher' => $data['is_teacher'] ?? false,
                //     ])
                //     ->log('Created new staff record');

                return $staff;
            } catch (\Exception $e) {
                // Log the error for debugging
                Log::error('Staff creation failed: ' . $e->getMessage(), [
                    'exception' => $e,
                    'data' => $data
                ]);

                throw $e;
            }
        });
    }

    protected function sendStaffLimitWarningNotification(School $school, FeatureCheckResult $result): void
    {
        $superAdmin = $school->getSuperAdmin();
        
        if (!$superAdmin) {
            return;
        }

        Notification::make()
            ->title('Staff Limit Warning')
            ->body("You have {$result->remaining} staff slot(s) remaining in your current plan.")
            ->warning()
            ->icon('heroicon-o-exclamation-triangle')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view_plans')
                    ->button()
                    ->url(route('filament.sms.pages.pricing-page', ['tenant' => $school->slug]))
                    ->label('View Plans'),
            ])
            ->sendToDatabase($superAdmin);
    }

    protected function sendStaffUserLimitReachedNotification(School $school): void
    {
        $superAdmin = $school->getSuperAdmin();
        
        if (!$superAdmin) {
            return;
        }

        Notification::make()
            ->title('Staff User Account Limit Reached')
            ->body('You have reached the maximum number of staff user accounts allowed in your current plan. Please upgrade to create more user accounts.')
            ->danger()
            ->icon('heroicon-o-x-circle')
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('upgrade')
                    ->button()
                    ->url(route('filament.sms.pages.pricing-page', ['tenant' => $school->slug]))
                    ->label('Upgrade Plan'),
            ])
            ->sendToDatabase($superAdmin);
    }

    /**
     * Override the default error handling to provide better messages
     */
    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Validation Error')
            ->body('Please check the form for errors and try again.')
            ->send();
    }

    /**
     * Additional method to handle cleanup if needed
     */
    protected function handleCreationFailure(\Exception $e, array $data): void
    {
        // Add any additional cleanup logic here
        Notification::make()
            ->danger()
            ->title('Error')
            ->body('An unexpected error occurred while creating the staff record. Please try again.')
            ->send();
    }

    protected function generatePassword(array $data): string
    {
        return match ($data['default_password_type'] ?? 'phone') {
            'email' => $data['email'],
            'custom' => $data['custom_password'],
            'phone' => $data['phone_number'],
            default => $data['phone_number'],
        };
    }

    protected function createUserAccount(Staff $staff, array $data, string $password): User
    {
        $userExist = User::where('email', $staff->email)->first();

        $staffActiveStatus = Status::where('type', 'staff')->where('name', 'active')->first();

        if ($userExist) {
            return $userExist;
        }
        $user = User::create([
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'email' => $staff->email,
            'status_id' => $staffActiveStatus->id,
            'user_type' => 'staff',
            'password' => Hash::make($password),
            
        ]);

        // Assign roles and permissions
        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        if (!empty($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }

        // Attach user to school
        $user->schools()->attach(Filament::getTenant()->id);

        return $user;
    }

    public function sendLoginCredentials(Staff $staff, string $password): void
    {
        try {
            Mail::to($staff->email)->send(new \App\Mail\StaffLoginCredentials(
                staff: $staff,
                password: $password,
                forcePasswordChange: $staff->force_password_change ?? true
            ));

            Notification::make()
                ->success()
                ->title('Login Credentials Sent')
                ->body("Login credentials have been sent to {$staff->email}")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->warning()
                ->title('Could Not Send Credentials')
                ->body("Login credentials could not be sent via email. Please provide them manually.")
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $tenant = Filament::getTenant();
        // activity()
        //     ->performedOn($this->record)
        //     ->causedBy(auth()->user())
        //     ->withProperties([
        //         'school_id' => $tenant->id,
        //         'action' => 'created',
        //     ])
        //     ->log('Created new staff record');
    }

    protected function beforeCreate(): void
    {
        // Check employee ID uniqueness
        $employeeId = $this->data['employee_id'];
        if (Staff::where('employee_id', $employeeId)->exists()) {
            Notification::make()
                ->danger()
                ->title('Duplicate Employee ID')
                ->body("The employee ID {$employeeId} is already in use. Please refresh the page to generate a new one.")
                ->send();

            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        $school = Filament::getTenant();
        $result = app(FeatureService::class)->checkResourceLimit($school, 'staff');

        // Only show remaining slots if we're close to the limit
        if ($result->remaining <= 5 && $result->remaining > 0) {
            return "Staff created successfully ({$result->remaining} staff slot" . 
                   ($result->remaining === 1 ? '' : 's') . " remaining)";
        }

        return "Staff created successfully";
    }
}
