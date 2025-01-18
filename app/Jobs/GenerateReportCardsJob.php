<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Traits\ZipsReportCards;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Services\ReportCardService;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateReportCardsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ZipsReportCards;

    protected $students;
    protected $termId;
    protected $sessionId;
    protected $templateId;
    // Remove this line since Batchable provides it
    // protected $batchId; 
    protected $school;
    protected $pdfPaths = [];
    protected $jobBatchId; // Use this instead for our custom batch ID
    protected $userId;
    protected $totalStudents;

    // Add retry configuration
    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 600; // 10 minutes
    public $adminNumber;

    public function __construct($students, $termId, $sessionId, $templateId, $jobBatchId, $school, $totalStudents)
    {
        $this->students = $students;
        $this->termId = $termId;
        $this->sessionId = $sessionId;
        $this->templateId = $templateId;
        $this->jobBatchId = $jobBatchId ?? $this->batch()?->id; // Use provided ID or get from batch
        $this->school = $school;
        $this->userId = auth()->id(); // Store the user ID
        $this->totalStudents = $totalStudents;
    }


    public function handle(ReportCardService $reportService)
    {
        $processedCount = 0;
        $chunkSize = 3; // Reduce chunk size for better reliability

        try {
            foreach ($this->students->chunk($chunkSize) as $chunk) {
                foreach ($chunk as $student) {
                    try {
                        Log::info('Processing student report', [
                            'student_id' => $student->id,
                            'progress' => "$processedCount/{$this->totalStudents}",
                            'batch_id' => $this->batch()?->id
                        ]);

                        $reportData = $reportService->generateReport(
                            $student,
                            $this->termId,
                            $this->sessionId,
                            $this->templateId
                        );

                        $pdfPath = $this->generatePdf($student, $reportData);
                        if ($pdfPath) {
                            $this->pdfPaths[] = $pdfPath;
                            $processedCount++;

                            // Update progress less frequently to reduce cache writes

                            $this->updateProgress($processedCount);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to generate student report', [
                            'student_id' => $student->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'processed_count' => $processedCount,
                            'total_students' => $this->totalStudents
                        ]);
                        continue; // Continue with next student instead of failing entire batch
                    }
                }

                if ($processedCount < $this->totalStudents) {
                    sleep(1);
                }
            }


            // Create zip only if we have processed some reports
            if ($processedCount > 0) {
                $this->finalizeReports();
            } else {
                throw new \Exception("No reports were successfully generated");
            }
        } catch (\Exception $e) {
            Log::error('Batch processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processed_count' => $processedCount,
                'total_students' => $this->totalStudents,
                'batch_id' => $this->batch()?->id
            ]);

            // Notify user of failure with details
            if ($user = User::find($this->userId)) {
                Notification::make()
                    ->title('Report Generation Failed')
                    ->danger()
                    ->body("Failed after processing {$processedCount} of {$this->totalStudents} reports. Error: {$e->getMessage()}")
                    ->sendToDatabase($user);
            }

            throw $e; // Re-throw to trigger job retry
        }
    }

    // Improved progress tracking method
    private function updateProgress(int $processedCount): void
    {
        if ($this->batch()) {
            // Calculate actual percentage
            $progress = ($processedCount / $this->totalStudents) * 100;

            // Update batch progress
            $this->batch()->progress($progress);

            // Cache processed count for UI
            Cache::put(
                "report_progress_{$this->batch()->id}",
                $processedCount,
                now()->addHours(1)
            );

            Log::info('Progress updated', [
                'processed' => $processedCount,
                'total' => $this->totalStudents,
                'progress' => $progress
            ]);
        }
    }

    protected function generatePdf($student, $reportData): ?string
    {
        // Create sanitized full name by combining first and last name
        $fullName = str("{$student->first_name} {$student->last_name}")
            ->replace(' ', '_')
            ->lower()
            ->ascii();

        // Create filename with full name, term and session
        // Ensure we pass all required arguments to sprintf
        $fileName = sprintf(
            '%s_term_%d_session_%d.pdf',
            $fullName,  // String for full name
            $this->termId,  // Integer for term
            $this->sessionId // Integer for session
        );

        $directory = sprintf(
            'reports/%s/%s/%s',
            $this->school->id,
            $this->sessionId,
            $this->termId
        );

        $pdfPath = $directory . '/' . $fileName;
        $fullPath = storage_path('app/' . $pdfPath);

        try {
            // Ensure directory exists
            if (!File::exists(dirname($fullPath))) {
                File::makeDirectory(dirname($fullPath), 0755, true);
            }

            $signatures = $this->prepareSignatures($reportData);

            // Get and prepare school logo data
            $schoolLogo = null;
            if ($this->school->logo) {
                $logoPath = storage_path('app/public/' . $this->school->logo);
                if (File::exists($logoPath)) {
                    $logoData = base64_encode(File::get($logoPath));
                    $mimeType = File::mimeType($logoPath);
                    $schoolLogo = "data:{$mimeType};base64,{$logoData}";
                }
            }

            $pdf = Pdf::view('pdfs.term-report-card-pdf', [
                'report' => $reportData,
                'school' => $this->school,
                'student' => $student,
                'schoolLogo' => $schoolLogo,
                'signatures' => $signatures,
                'isPdfMode' => true
            ])
                ->format('a4')
                ->withBrowsershot(function (Browsershot $browsershot) {
                    $browsershot->setChromePath(config('app.chrome_path'))
                        ->format('A4')
                        ->margins(5, 5, 5, 5)
                        ->showBackground()
                        ->waitUntilNetworkIdle()
                        ->setNodeModulesPath(base_path('node_modules'))
                        ->noSandbox()
                        ->dismissDialogs();
                });

            $pdf->save($fullPath);

            if (!File::exists($fullPath)) {
                throw new \Exception("Failed to create PDF at {$fullPath}");
            }

            return $pdfPath;
        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    private function prepareSignatures(array $reportData): array
    {
        $signatures = [
            'class_teacher' => null,
            'principal' => null
        ];

        foreach (['class_teacher', 'principal'] as $type) {
            if (!empty($reportData['comments'][$type]['digital_signature']['signature_url'])) {
                $url = $reportData['comments'][$type]['digital_signature']['signature_url'];
                $pathParts = explode('/storage/', parse_url($url)['path']);
                $signaturePath = end($pathParts);

                $fullPath = storage_path('app/public/' . $signaturePath);

                if (File::exists($fullPath)) {
                    $signatureData = base64_encode(File::get($fullPath));
                    $mimeType = File::mimeType($fullPath);
                    $signatures[$type] = "data:{$mimeType};base64,{$signatureData}";
                }
            }
        }

        return $signatures;
    }

    // protected function finalizeReports(): void
    // {
    //     if (empty($this->pdfPaths)) {
    //         Log::warning('No PDFs to process', ['batch_id' => $this->jobBatchId]);

    //         // Notify user of empty result
    //         if ($user = User::find($this->userId)) {
    //             Notification::make()
    //                 ->title('No Reports Generated')
    //                 ->warning()
    //                 ->body('No report cards were generated. Please check if the students have grades recorded.')
    //                 ->sendToDatabase($user);
    //         }
    //         return;
    //     }

    //     try {
    //         // Ensure we have a batch ID
    //         $batchId = $this->jobBatchId ?? $this->batch()?->id;
    //         if (!$batchId) {
    //             throw new \Exception("No batch ID available for zip creation");
    //         }

    //         // Get class ID from the first student
    //         $classId = $this->students->first()->class_room_id;

    //         // Cache batch info
    //         $batchInfoKey = "batch_info_" . $this->userId . "_" . $this->school->id;
    //         Cache::put($batchInfoKey, [
    //             'class_id' => $classId,
    //             'batch_id' => $batchId,
    //         ], now()->addHour());

    //         Log::info('Caching batch info', [
    //             'key' => $batchInfoKey,
    //             'data' => [
    //                 'class_id' => $classId,
    //                 'batch_id' => $batchId
    //             ]
    //         ]);

    //         // $zipPath = $this->createReportCardsZip(
    //         //     $this->pdfPaths,
    //         //     $batchId,
    //         //     "{$school}_{$class}"
    //         // );
    //         $zipPath = $this->createReportCardsZip(
    //             $this->pdfPaths,
    //             $batchId,
    //             $classId
    //         );

    //         if ($zipPath) {
    //             $this->cleanupPdfs($this->pdfPaths);

    //             // Cache the download URL
    //             $cacheKey = "report_zip_url_{$classId}_{$batchId}";
    //             $downloadUrl = Storage::url($zipPath);
    //             Cache::put($cacheKey, $downloadUrl, now()->addHours(24));

    //             Log::info('Cached download URL', [
    //                 'key' => $cacheKey,
    //                 'url' => $downloadUrl
    //             ]);

    //             // Notify user
    //             if ($user = User::find($this->userId)) {
    //                 Notification::make()
    //                     ->title('Report Cards Ready')
    //                     ->success()
    //                     ->body('Your report cards have been generated successfully.')
    //                     ->actions([
    //                         \Filament\Notifications\Actions\Action::make('download')
    //                             ->label('Download Reports')
    //                             ->url($downloadUrl)
    //                             ->openUrlInNewTab()
    //                     ])
    //                     ->sendToDatabase($user);
    //             }

    //             Log::info('Successfully created report cards zip', [
    //                 'batch_id' => $batchId,
    //                 'zip_path' => $zipPath
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Failed to finalize reports', [
    //             'batch_id' => $this->jobBatchId,
    //             'batch_object_id' => $this->batch()?->id,
    //             'error' => $e->getMessage()
    //         ]);

    //         // Notify user of failure
    //         if ($user = User::find($this->userId)) {
    //             Notification::make()
    //                 ->title('Report Cards Generation Failed')
    //                 ->danger()
    //                 ->body('There was an error generating your bulk report cards. Please try again.')
    //                 ->sendToDatabase($user);
    //         }
    //     }
    // }

    protected function finalizeReports(): void
    {
        if (empty($this->pdfPaths)) {
            Log::warning('No PDFs to process', ['batch_id' => $this->jobBatchId]);
            return;
        }

        try {
            $batchId = $this->jobBatchId ?? $this->batch()?->id;
            if (!$batchId) {
                throw new \Exception("No batch ID available for zip creation");
            }

            // Get first student to get class details
            $firstStudent = $this->students->first();

            // Get readable class name for notification
            $readableClassName = str($firstStudent->classRoom->name)
                ->title()
                ->toString();

            // Create sanitized names for school and class
            $schoolName = str($this->school->name)
                ->replace(' ', '_')
                ->lower()
                ->ascii();

            $className = str($firstStudent->classRoom->name)
                ->replace(' ', '_')
                ->lower()
                ->ascii();

            // Combine for folder name
            $folderName = "{$schoolName}_{$className}";

            // Store actual class ID for reference but use descriptive name for storage
            $batchInfoKey = "batch_info_" . $this->userId . "_" . $this->school->id;
            Cache::put($batchInfoKey, [
                'class_id' => $firstStudent->class_room_id,
                'folder_name' => $folderName,
                'batch_id' => $batchId,
            ], now()->addHour());

            $zipPath = $this->createReportCardsZip(
                $this->pdfPaths,
                $batchId,
                $folderName
            );

            if ($zipPath) {
                $this->cleanupPdfs($this->pdfPaths);

                // Cache with descriptive name
                $cacheKey = "report_zip_url_{$folderName}_{$batchId}";
                $downloadUrl = Storage::url($zipPath);
                Cache::put($cacheKey, $downloadUrl, now()->addHours(24));

                // Get total number of reports generated
                $totalReports = count($this->pdfPaths);

                // Notify user with more specific information
                if ($user = User::find($this->userId)) {
                    Notification::make()
                        ->title("Report Cards Ready for {$readableClassName}")
                        ->success()
                        ->body("Successfully generated {$totalReports} report cards for {$readableClassName} students.")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('download')
                                ->label('Download Reports')
                                ->url($downloadUrl)
                                ->openUrlInNewTab()
                        ])
                        ->sendToDatabase($user);
                }
            }
        } catch (\Exception $e) {
            // More detailed error notification
            if ($user = User::find($this->userId)) {
                Notification::make()
                    ->title('Report Generation Failed')
                    ->danger()
                    ->body("Failed to generate reports for {$readableClassName}. Please try again or contact support.")
                    ->persistent()
                    ->sendToDatabase($user);
            }

            Log::error('Failed to finalize reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class' => $readableClassName ?? 'Unknown',
                'total_pdfs' => count($this->pdfPaths)
            ]);
        }
    }
    public function failed(\Throwable $exception)
    {
        Log::error('Job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'batch_id' => $this->batch()?->id
        ]);

        if ($user = User::find($this->userId)) {
            Notification::make()
                ->title('Report Generation Failed')
                ->danger()
                ->body('The report generation process failed. Please try again or contact support.')
                ->sendToDatabase($user);
        }
    }
}
