<?php

namespace App\Filament\Sms\Resources\StaffResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use App\Services\EmployeeIdGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Sms\Resources\StaffResource;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('regenerateIds')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('This will regenerate all employee IDs using the current settings. To change ID format settings, please use the Manage Settings page.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();
                    $settings = $tenant->settings;
                    
                    // Use existing settings from database
                    $generator = new EmployeeIdGenerator($settings);
                    $generator->regenerateAllIds();

                    Cache::tags(["school:{$tenant->slug}"])->flush();

                    Notification::make()
                        ->success()
                        ->title('Employee IDs Regenerated')
                        ->body('All IDs have been updated using current settings.')
                        ->send();
                }),
        ];
    }
}
