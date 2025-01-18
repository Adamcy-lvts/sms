<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\AdmissionImporter;
use App\Filament\Sms\Resources\AdmissionResource;

class ListAdmissions extends ListRecords
{
    protected static string $resource = AdmissionResource::class;

    protected function getHeaderActions(): array
    {
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
                // ->color('primary')
                ->icon('heroicon-o-arrow-up-tray')
        ];
    }
}
