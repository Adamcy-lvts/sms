<?php

namespace App\Filament\Sms\Pages;

use Carbon\Carbon;
use App\Models\Term;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Spatie\LaravelPdf\Facades\Pdf;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Filament\Forms\Components\Select;
use App\Services\FinancialReportService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;

class FinancialReports extends Page
{
    // Define view path
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Financial Reports';
    protected static ?string $title = 'Financial Reports';
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.sms.pages.financial-reports';

    // Properties
    public $periodType = 'month';
    public $periodId;
    public $sessionId;
    public $termId;
    public $report;
    public $isLoading = false;

    // Add property for service
    protected FinancialReportService $reportService;

    // Constructor injection
    public function boot(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    // Mount method
    public function mount(): void
    {
        $this->periodType = 'month';
        $this->periodId = now()->format('Y-m');
    }

    // Form Schema
    protected function getFormSchema(): array
    {
        return [
            // Period Type Select
            Select::make('periodType')
                ->label('Report Period')
                ->native(false)
                ->options([
                    'month' => 'Monthly',
                    'term' => 'Term',
                    'session' => 'Session'
                ])
                ->default('month')
                ->required()
                ->live() // Make it reactive
                ->afterStateUpdated(fn() => $this->reset(['sessionId', 'termId'])), // Reset dependent fields

            // Dynamic Period Selector based on type
            $this->getPeriodSelector(),
        ];
    }

    protected function getPeriodSelector()
    {
        $tenant = Filament::getTenant();

        return match ($this->periodType) {
            'month' => DatePicker::make('periodId')
                ->label('Select Month')
                ->native(false)
                ->displayFormat('M Y')
                ->firstDayOfWeek(1)
                ->closeOnDateSelection()
                ->required(),

            'term' => Grid::make(2)
                ->schema([
                    // Session select for term period type
                    Select::make('sessionId')
                        ->label('Academic Session')
                        ->native(false)
                        ->options(function () use ($tenant) {
                            return $tenant->academicSessions()
                                ->orderByDesc('start_date')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->live() // Make it reactive
                        ->required(),

                    // Term select dependent on session
                    Select::make('termId')
                        ->label('Term')
                        ->native(false)
                        ->options(function (callable $get) use ($tenant) {
                            $sessionId = $get('sessionId');
                            if (!$sessionId) return [];

                            return $tenant->terms()
                                ->where('academic_session_id', $sessionId)
                                ->orderBy('start_date')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->visible(fn(callable $get) => filled($get('sessionId'))) // Only show when session selected
                        ->dehydrated(false), // Don't include in form data if not needed
                ]),

            'session' => Select::make('sessionId')
                ->label('Academic Session')
                ->native(false)
                ->options(function () use ($tenant) {
                    return $tenant->academicSessions()
                        ->orderByDesc('start_date')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->required(),

            default => null,
        };
    }

    // Generate Report Method
    public function generateReport(): void
    {
        // Validate inputs
        $this->validate([
            'periodType' => 'required',
            'periodId' => 'required'
        ]);

        try {
            $this->isLoading = true;

            // Ensure reportService is available
            if (!isset($this->reportService)) {
                throw new \Exception('Report service not initialized');
            }

            // Determine period ID based on type
            $periodId = match ($this->periodType) {
                'month' => $this->periodId,
                'term' => $this->termId,
                'session' => $this->sessionId,
            };

            $this->report = $this->reportService->generateReport(
                $this->periodType,
                $periodId
            );
            // dd($this->report);
            // Add helper method to check if category has items
            $this->report['expenses']['by_category'] = collect($this->report['expenses']['by_category'])
                ->map(function ($category) {
                    $category['has_items'] = !empty($category['items']);
                    return $category;
                })
                ->toArray();

            // Success notification
            Notification::make()
                ->title('Report Generated Successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Log error
            Log::error('Financial Report Generation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'periodType' => $this->periodType,
                'periodId' => $this->periodId
            ]);

            // Error notification
            Notification::make()
                ->title('Failed to Generate Report')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('download')
                ->label('Download Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn() => $this->downloadReport()),

            \Filament\Actions\Action::make('print')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->action(fn() => $this->printReport()),
        ];
    }

    /**
     * Generate and download financial report as PDF
     */
    protected function downloadReport()
    {
        try {
            $school = Filament::getTenant();
            $period = match ($this->periodType) {
                'term' => Term::find($this->periodId)?->name,
                'session' => AcademicSession::find($this->periodId)?->name,
                default => Carbon::parse($this->periodId)->format('F Y')
            };

            $fileName = str($school->name . '-financial-report-' . $period)->slug() . '.pdf';

            return response()->streamDownload(function () use ($period) {
                $pdf = Pdf::view('pdfs.financial-report', [
                    'report' => $this->report,
                    'school' => Filament::getTenant(),
                    'period' => $period
                ])
                    ->format('a4')
                    ->withBrowsershot(function (Browsershot $browsershot) {
                        $browsershot->setChromePath(config('app.chrome_path'))
                            ->format('A4')
                            ->margins(20, 20, 20, 20)
                            ->showBackground()
                            ->scale(1)
                            ->waitUntilNetworkIdle();
                    });

                echo $pdf->toString();
            }, $fileName);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error Generating PDF')
                ->body('Failed to generate PDF report.')
                ->send();
        }
    }

    /**
     * Generate print-optimized version of report
     */
    protected function printReport()
    {
        try {
            // Use JavaScript to open a new window with print-friendly view
            $this->dispatch('print-report', [
                'url' => route('financial-reports.print', [
                    'tenant' => Filament::getTenant()->slug,
                    'type' => $this->periodType,
                    'id' => $this->periodId
                ])
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Print Error')
                ->body('Failed to prepare print view.')
                ->send();
        }
    }
}
