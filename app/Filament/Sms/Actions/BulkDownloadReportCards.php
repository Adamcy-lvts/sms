<?php

namespace App\Filament\Sms\Actions;

use Throwable;
use Illuminate\Bus\Batch;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Livewire\ReportProgress;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerateReportCardsJob;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Cache;
use App\Filament\Sms\Pages\TermReports;
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
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');
        $this->form([
            Select::make('class_room_id')
                ->label('Class')
                ->options(fn() => Filament::getTenant()->classRooms()->pluck('name', 'id'))
                ->required(),

            Select::make('academic_session_id')
                ->label('Academic Session')
                ->native(false)
                ->options(function () use ($tenant) {
                    return $tenant->academicSessions()
                        ->orderByDesc('start_date')
                        ->pluck('name', 'id');
                })
                ->default(fn() => $currentSession?->id)
                ->live()
                ->required(),

            Select::make('term_id')
                ->label('Term')
                ->native(false)
                ->options(function (callable $get) use ($currentSession) {
                    // let's ge tthe terms directly from the academic session
                    $terms = $currentSession->terms;

                    return $terms->pluck('name', 'id');
                })
                ->default(function (callable $get) use ($currentSession, $currentTerm) {
                    // Only set default if the selected session matches current session
                    return $get('academic_session_id') === $currentSession?->id
                        ? $currentTerm?->id
                        : null;
                })
                ->disabled(fn(callable $get) => !$get('academic_session_id'))
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

                // Store class info in cache
                $cacheKey = "report_batch_info_" . auth()->id() . "_" . Filament::getTenant()->id;
                Cache::put($cacheKey, [
                    'class_id' => $classRoom->id,
                    'class_name' => $classRoom->name,
                ], now()->addHour());

                // Generate unique cache key for this class
                $cacheKey = sprintf(
                    'report_batch_%s_%s',
                    $classRoom->id,
                    Str::random(8)
                );

                // Clear previous class-specific caches
                $this->clearPreviousClassCache($classRoom->id);

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

    protected function clearPreviousClassCache($classId): void
    {
        $pattern = "report_batch_*_{$classId}_*";
        foreach (Cache::get($pattern) ?? [] as $key) {
            Cache::forget($key);
            // Also delete associated files
            $this->deleteAssociatedFiles($key);
        }
    }
}
