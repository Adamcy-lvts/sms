<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Models\School;
use App\Models\Status;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Filament\Resources\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            // 1. Get active status
            $activeStatusId = Status::where('name', 'active')
                ->where('type', 'user')
                ->first()
                ->id;

            // 2. Create School Administrator User
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['admin_email'], // Using admin email for authentication
                'password' => $data['password'] ,
                'status_id' => $activeStatusId
            ]);

            // 3. Create School record
            $school = School::create([
                'name' => $data['name'],
                'name_ar' => $data['name_ar'] ?? null,
                'email' => $data['email'],
                'slug' => Str::slug($data['name']),
                'address' => $data['address'],
                'phone' => $data['phone'],
                'logo' => $data['logo'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
            ]);

            // 4. Create School Settings
            // $school->schoolSettings()->create([
            //     'theme_color' => $data['theme_color'] ?? '#000000',
            //     'allow_registration' => $data['allow_registration'] ?? true,
            //     'timezone' => $data['timezone'] ?? 'Africa/Lagos',
            //     'academic_year_start' => $data['academic_year_start'] ?? now()->startOfYear(),
            // ]);

            // 5. Associate user with school
            $school->members()->attach($user->id);

            // 6. Commit transaction
            DB::commit();

            // 7. Show success notification
            Notification::make()
                ->success()
                ->title('School Created')
                ->body('School has been created successfully.')
                ->send();

            return $school;
        } catch (\Exception $e) {
            // Rollback on error
            DB::rollBack();

            // Log the error
            Log::error('School Creation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);

            // Show error notification
            Notification::make()
                ->danger()
                ->title('Creation Failed')
                ->body('Failed to create school. Please try again.')
                ->persistent()
                ->send();

            throw $e;
        }
    }
}
