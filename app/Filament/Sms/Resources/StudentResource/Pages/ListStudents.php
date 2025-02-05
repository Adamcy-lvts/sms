<?php

namespace App\Filament\Sms\Resources\StudentResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Filament\Actions\ImportAction;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Select;
use App\Imports\StudentAdmissionImport;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Sms\Resources\StudentResource;
use App\Filament\Imports\StudentAdmissionImporter;
use App\Services\FeatureService;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        $school = Filament::getTenant();
        $featureService = app(FeatureService::class);
        
        // Separate feature check from limit check
        $hasBulkDataFeature = $featureService->hasFeatureAccess($school, 'bulk_data');
        $limitCheck = $featureService->checkResourceLimit($school, 'students');
        $canImport = auth()->user()->can('import_student');

        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(StudentAdmissionImporter::class)
                ->label('Import Students')
                ->modalHeading('Import Students')
                ->modalDescription('Upload a CSV file to import students.')
                ->modalSubmitActionLabel('Import')
                ->modalCancelActionLabel('Cancel')
                ->closeModalByClickingAway(false)
                ->icon('heroicon-o-arrow-up-tray')
                ->visible($hasBulkDataFeature && $canImport) // Added permission check here
                ->disabled(!$limitCheck->allowed)
                ->tooltip(!$hasBulkDataFeature 
                    ? 'Bulk data import is not available in your current plan'
                    : (!$canImport 
                        ? 'You do not have permission to import students'
                        : ($limitCheck->allowed ? null : $limitCheck->message))),
        ];
    }
}
