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

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        $tenant = Filament::getTenant();
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(StudentAdmissionImporter::class)
                ->label('Import Students')
                ->modalHeading('Import Students from CSV')
                // ->modalDescription('Upload a CSV file to import students. Please make sure your file follows the correct format.')
                ->modalSubmitActionLabel('Import')
                ->modalCancelActionLabel('Cancel')
                ->closeModalByClickingAway(false)
                ->successNotificationTitle('Import completed')
                ->failureNotificationTitle('Import failed')
                ->icon('heroicon-o-arrow-up-tray')
            // Action::make('import')
            //     ->label('Import Students')
            //     ->form([
            //         FileUpload::make('excel_file')
            //             ->label('Excel File')
            //             ->required()
            //             ->acceptedFileTypes([
            //                 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            //                 'application/vnd.ms-excel'
            //             ])
            //             ->maxSize(5120)
            //             ->disk('public')->directory("{$tenant->slug}/documents")
            //             ->visibility('private')
            //             ->storeFileNamesIn('original_filename'),

            //     ])
            //     ->action(function (array $data): void {
            //         try {
            //             $import = new StudentAdmissionImport();
            //             Excel::import($import, Storage::disk('public')->path($data['excel_file']));

            //             // Show single success message if there were successful imports
            //             if (count($import->getSuccesses()) > 0) {
            //                 Notification::make()
            //                     ->success()
            //                     ->title('Import Completed')
            //                     ->body($import->getSuccesses()[0])
            //                     ->send();
            //             }

            //             // Show single error message if there were errors
            //             if (count($import->getErrors()) > 0) {
            //                 Notification::make()
            //                     ->danger()
            //                     ->title('Import Errors')
            //                     ->body(implode("\n", $import->getErrors()))
            //                     ->seconds(30)
            //                     ->send();
            //             }

            //             // Clean up the imported file
            //             Storage::delete($data['excel_file']);
            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->danger()
            //                 ->title('Import Failed')
            //                 ->body('An error occurred during import: ' . $e->getMessage())
            //                 ->send();

            //             // Clean up on error
            //             if (isset($data['excel_file'])) {
            //                 Storage::delete($data['excel_file']);
            //             }
            //         }
            //     })
            //     ->modalWidth('lg')
            //     ->modalHeading('Import Students')
            //     ->modalDescription('Upload an Excel file containing student information. The file should include all required fields in the correct format.')
            //     ->modalSubmitActionLabel('Upload and Import')
            //     ->modalCancelActionLabel('Cancel')
            //     ->successNotificationTitle('Import Completed')
            // ->successNotificationMessage('Students have been imported successfully.');
        ];
    }
}
