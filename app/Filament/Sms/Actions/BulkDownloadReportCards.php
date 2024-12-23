<?php

namespace App\Filament\Sms\Actions;

use App\Filament\Sms\Pages\TermReports;
use App\Livewire\ReportProgress;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use App\Services\BulkReportCardService;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class BulkDownloadReportCards extends Action
{
    public static function getDefaultName(): string
    {
        return 'bulkDownloadReports';
    }

    protected function setUp(): void
    {
        $tenant = Filament::getTenant();
        parent::setUp();

        $this->form([
            Select::make('class_room_id')
                ->label('Class')
                ->options(fn() => Filament::getTenant()->classRooms()->pluck('name', 'id'))
                ->required(),

            Select::make('academic_session_id')
                ->label('Academic Session')
                ->options(function () use ($tenant) {
                    return $tenant->academicSessions()
                        ->pluck('name', 'id');
                })
                ->default(fn() => config('app.current_session')->id)
                ->required(),

            Select::make('term_id')
                ->label('Term')
                ->options(function () use ($tenant) {
                    return $tenant->terms()
                        ->pluck('name', 'id');
                })
                ->default(fn() => config('app.current_term')->id)
                ->required(),

            Select::make('template_id')
                ->label('Report Template')
                ->options(fn() => Filament::getTenant()->reportTemplates()
                    ->where('is_active', true)
                    ->pluck('name', 'id'))
                ->placeholder('Use default template'),
        ]);

        $this->action(function (array $data, BulkReportCardService $service): void {
            try {
                $classRoom = Filament::getTenant()->classRooms()->findOrFail($data['class_room_id']);

                $batchId = $service->startBulkGeneration(
                    $classRoom,
                    $data['term_id'],
                    $data['academic_session_id'],
                    $data['template_id'] ?? null
                );

                Notification::make()
                    ->success()
                    ->title('Report generation started')
                    ->send();

                // Simply redirect to TermReports page
                $this->redirect(TermReports::getUrl());

                // Close the form modal
                // $this->success();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error starting report generation')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        });

        $this->label('Bulk Download Reports')
            ->modalHeading('Download Class Report Cards')
            ->modalDescription('Generate and download report cards for all students in a class.')
            ->modalSubmitActionLabel('Generate Reports');
    }
}
