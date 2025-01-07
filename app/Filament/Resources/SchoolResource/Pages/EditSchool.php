<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SchoolResource;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Get the admin user
        $admin = $this->record->members()->first();

        // Fill the form with combined school and admin data
        $this->form->fill([
            // School data
            'name' => $this->record->name,
            'name_ar' => $this->record->name_ar,
            'slug' => $this->record->slug,
            'email' => $this->record->email,
            'phone' => $this->record->phone,
            'address' => $this->record->address,
            'logo' => $this->record->logo,
            'agent_id' => $this->record->agent_id,

            // Admin data
            'admin_email' => $admin?->email,
            'first_name' => $admin?->first_name,
            'last_name' => $admin?->last_name,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            DB::beginTransaction();

            // Update school record
            $record->update([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'name_ar' => $data['name_ar'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'logo' => $data['logo'] ?? $record->logo,
                'agent_id' => $data['agent_id'] ?? null,
            ]);

            // Update administrator user
            if ($admin = $record->members()->first()) {
                $adminData = [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['admin_email'],
                ];

                if (!empty($data['password'])) {
                    $adminData['password'] = $data['password'];
                }

                $admin->update($adminData);
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('School Updated')
                ->body('School details updated successfully.')
                ->send();

            return $record;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('School Update Error', [
                'school_id' => $record->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->danger()
                ->title('Update Failed')
                ->body('Failed to update school details.')
                ->persistent()
                ->send();

            throw $e;
        }
    }

    public function getSavedNotificationMessage(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
