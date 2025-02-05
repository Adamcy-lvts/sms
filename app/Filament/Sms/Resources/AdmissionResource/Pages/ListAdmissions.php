<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\AdmissionImporter;
use App\Filament\Sms\Resources\AdmissionResource;
use App\Services\FeatureService;
use App\Services\AdmissionNumberGenerator;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class ListAdmissions extends ListRecords
{
    protected static string $resource = AdmissionResource::class;

    protected function getHeaderActions(): array
    {
        $school = Filament::getTenant();
        $featureService = app(FeatureService::class);
        
        // Check both bulk data feature and admission limits
        $hasBulkDataFeature = $featureService->hasFeatureAccess($school, 'bulk_data');
      

        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(AdmissionImporter::class)
                ->label('Import Admissions')
                ->modalHeading('Import Students from CSV')
                ->modalSubmitActionLabel('Import')
                ->modalCancelActionLabel('Cancel')
                ->closeModalByClickingAway(false)
                ->successNotificationTitle('Import completed')
                ->failureNotificationTitle('Import failed')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible($hasBulkDataFeature),
            Actions\Action::make('regenerateIds')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('This will regenerate all admission numbers using the current settings. To change number format settings, please use the Manage Settings page.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();
                    $settings = $tenant->settings;
                    
                    // Use existing settings from database
                    $generator = new AdmissionNumberGenerator();
                    $generator->regenerateAllIds();

                    Cache::tags(["school:{$tenant->slug}"])->flush();

                    Notification::make()
                        ->success()
                        ->title('Admission Numbers Regenerated')
                        ->body('All numbers have been updated using current settings.')
                        ->send();
                }),
        ];
    }
}
