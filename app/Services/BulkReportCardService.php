<?php

namespace App\Services;

use Throwable;
use ZipArchive;
use App\Models\School;
use App\Models\Student;
use App\Models\ClassRoom;
use Illuminate\Bus\Batch;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use App\Jobs\CreateReportZip;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Jobs\GenerateStudentReportPDF;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class BulkReportCardService
{
    protected $reportCardService;
    protected $gradeService;
    protected $statusService;
    public $userId;

    public function __construct(
        ReportCardService $reportCardService,
        GradeService $gradeService,
        StatusService $statusService
    ) {
        $this->reportCardService = $reportCardService;
        $this->gradeService = $gradeService;
        $this->statusService = $statusService;
    }

    public function startBulkGeneration(ClassRoom $classRoom, int $termId, int $academicSessionId, ?int $templateId = null): string
    {
        $this->userId = auth()->id();
        try {
            Log::info('Starting bulk report generation', [
                'class_room' => $classRoom->id,
                'term_id' => $termId,
                'session_id' => $academicSessionId
            ]);

            // Create batch ID
            $batchId = Str::uuid()->toString();

            // Create consistent temp directory path
            $tempDir = storage_path("app/temp/reports/{$batchId}");
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Get active students
            $students = Student::where('class_room_id', $classRoom->id)
                ->where('status_id', $this->statusService->getActiveStudentStatusId())
                ->get();

            if ($students->isEmpty()) {
                throw new \Exception('No active students found in this class.');
            }

            // Prepare school logo
            $schoolLogo = $this->prepareSchoolLogo($classRoom->school);

            // Create jobs collection
            $jobs = $students->map(function ($student) use ($termId, $academicSessionId, $templateId, $tempDir, $schoolLogo) {
                return new GenerateStudentReportPDF(
                    $student,
                    $termId,
                    $academicSessionId,
                    $templateId,
                    $tempDir,
                    $schoolLogo
                );
            });

            // Create and configure the batch
            $batch = Bus::batch($jobs)
                ->name("Generate Report Cards - {$classRoom->name}")
                ->allowFailures()
                ->then(function (Batch $batch) use ($tempDir, $batchId) {
                    Log::info('All reports generated successfully', [
                        'batch_id' => $batch->id,
                        'total_jobs' => $batch->totalJobs
                    ]);

                    // Create ZIP file using the same temp directory
                    dispatch(new CreateReportZip($batch->id, $tempDir,$this->userId))
                        ->onQueue('pdf-generation');
                })
                // Remove the finally callback that was cleaning up the directory
                ->onQueue('pdf-generation')
                ->dispatch();
                cache(['report_batch_id_' . auth()->id() => $batch->id], now()->addHour());
            Log::info('Batch created successfully', [
                'batch_id' => $batch->id,
                'batche' => $batch
            ]);

            return $batch->id;
        } catch (\Exception $e) {
            Log::error('Failed to start bulk generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            throw $e;
        }
    }

    protected function prepareSchoolLogo(School $school): ?string
    {
        if (!$school->logo) {
            return null;
        }

        $logoPath = storage_path('app/public/' . $school->logo);
        if (!File::exists($logoPath)) {
            return null;
        }

        try {
            $logoData = base64_encode(File::get($logoPath));
            $mimeType = File::mimeType($logoPath);
            return "data:{$mimeType};base64,{$logoData}";
        } catch (\Exception $e) {
            Log::warning('Failed to prepare school logo', [
                'school_id' => $school->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function checkBatchStatus(string $batchId): array
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return [
                'error' => 'Batch not found',
                'progress' => 0,
                'status' => 'not_found'
            ];
        }

        $zipFile = null;
        if ($batch->finished()) {
            $zipFile = "report-cards-{$batchId}.zip";

            // Verify zip file exists
            if (!Storage::disk('public')->exists("reports/bulk/{$zipFile}")) {
                $zipFile = null;
            }
        }

        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'totalJobs' => $batch->totalJobs,
            'pendingJobs' => $batch->pendingJobs,
            'processedJobs' => $batch->processedJobs(),
            'failedJobs' => $batch->failedJobs,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
            'status' => $this->getBatchStatusLabel($batch),
            'zipFile' => $zipFile
        ];
    }

    protected function getBatchStatusLabel(Batch $batch): string
    {
        if ($batch->cancelled()) {
            return 'cancelled';
        }

        if ($batch->finished()) {
            return $batch->failedJobs > 0 ? 'completed_with_failures' : 'completed';
        }

        return 'processing';
    }

    public function cancelBatch(string $batchId): bool
    {
        try {
            $batch = Bus::findBatch($batchId);

            if (!$batch) {
                throw new \Exception('Batch not found');
            }

            $batch->cancel();

            // Clean up any temp files
            $tempDir = storage_path("app/temp/reports/batch_{$batchId}");
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            Notification::make()
                ->title('Report Generation Cancelled')
                ->body('The report generation process has been cancelled.')
                ->warning()
                ->send();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
