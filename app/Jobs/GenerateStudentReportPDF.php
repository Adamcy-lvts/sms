<?php

// namespace App\Jobs;

// use App\Models\Student;
// use App\Models\ClassRoom;
// use Illuminate\Support\Str;
// use Illuminate\Bus\Queueable;
// use Spatie\LaravelPdf\Facades\Pdf;
// use Illuminate\Support\Facades\Log;
// use Spatie\Browsershot\Browsershot;
// use Illuminate\Support\Facades\File;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;

// class GenerateStudentReportPDF implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     public function __construct(
//         protected Student $student,
//         protected string $batchId,
//         protected int $termId,
//         protected int $academicSessionId,
//         protected ?int $templateId,
//         protected string $tempDir,
//         protected string $schoolLogo,
//         protected int $totalStudents
//     ) {}

//     public function handle()
//     {

//         Log::info('Starting PDF generation job', [
//             'student_id' => $this->student->id,
//             'batch_id' => $this->batchId
//         ]);


//         try {
//             // Check if directories exist before starting
//             Log::info('Checking directories', [
//                 'temp_dir' => $this->tempDir,
//                 'temp_dir_exists' => File::exists($this->tempDir)
//             ]);
//             // Ensure the temp directory exists
//             if (!File::exists($this->tempDir)) {
//                 File::makeDirectory($this->tempDir, 0755, true);
//             }

//             $reportCardService = app(\App\Services\ReportCardService::class);

//             $reportData = $reportCardService->generateReport(
//                 $this->student,
//                 $this->termId,
//                 $this->academicSessionId,
//                 $this->templateId
//             );

//             $signatures = $this->prepareSignatures($reportData);

//             // Sanitize filename
//             $fileName = Str::slug($this->student->admission_number . '-' . $this->student->full_name) . '.pdf';
//             $filePath = $this->tempDir . '/' . $fileName;

//             // Ensure directory for PDF exists
//             $pdfDir = dirname($filePath);
//             if (!File::exists($pdfDir)) {
//                 File::makeDirectory($pdfDir, 0755, true);
//             }

//             $pdf = Pdf::view('pdfs.term-report-card-pdf', [
//                 'report' => $reportData,
//                 'school' => $this->student->school,
//                 'student' => $this->student,
//                 'schoolLogo' => $this->schoolLogo,
//                 'signatures' => $signatures,
//                 'isPdfMode' => true
//             ])
//                 ->format('a4')
//                 ->withBrowsershot(function (Browsershot $browsershot) {
//                     $browsershot->setChromePath(config('app.chrome_path'))
//                         ->format('A4')
//                         ->margins(5, 5, 5, 5)
//                         ->showBackground()
//                         ->waitUntilNetworkIdle()
//                         ->setNodeModulesPath(base_path('node_modules'))
//                         ->noSandbox()
//                         ->dismissDialogs();
//                 });

//             // Save PDF
//             $pdf->save($filePath);

//             if (!File::exists($filePath)) {
//                 throw new \Exception("Failed to create PDF at {$filePath}");
//             }

//             // Update progress
//             $processed = Cache::increment("report_progress_{$this->batchId}");

//             Log::info('PDF generation completed successfully', [
//                 'student_id' => $this->student->id,
//                 'batch_id' => $this->batchId
//             ]);
//             // If all PDFs are generated, create ZIP
//             if ($processed === $this->totalStudents) {
//                 $zipJob = new CreateReportZip($this->batchId, $this->tempDir);
//                 dispatch($zipJob);
                
//                 Log::info('Dispatched ZIP creation job', [
//                     'batch_id' => $this->batchId,
//                     'processed_count' => $processed
//                 ]);
//             }
//         } catch (\Exception $e) {
//             logger()->error('PDF Generation Failed', [
//                 'student' => $this->student->id,
//                 'error' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString(),
//                 'temp_dir' => $this->tempDir,
//                 'file_path' => $filePath ?? null
//             ]);
//             Cache::increment("report_failed_{$this->batchId}");
//         }
//     }

//     private function prepareSignatures(array $reportData): array
//     {
//         $signatures = [
//             'class_teacher' => null,
//             'principal' => null
//         ];

//         foreach (['class_teacher', 'principal'] as $type) {
//             if (!empty($reportData['comments'][$type]['digital_signature']['signature_url'])) {
//                 $url = $reportData['comments'][$type]['digital_signature']['signature_url'];
//                 $parsedUrl = parse_url($url);
//                 $pathParts = explode('/storage/', $parsedUrl['path']);
//                 $signaturePath = end($pathParts);

//                 $fullPath = storage_path('app/public/' . $signaturePath);

//                 if (File::exists($fullPath)) {
//                     $signatureData = base64_encode(File::get($fullPath));
//                     $mimeType = File::mimeType($fullPath);
//                     $signatures[$type] = "data:{$mimeType};base64,{$signatureData}";
//                 }
//             }
//         }

//         return $signatures;
//     }
// }

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateStudentReportPDF implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Student $student,
        protected int $termId,
        protected int $academicSessionId,
        protected ?int $templateId,
        protected string $tempDir,
        protected ?string $schoolLogo
    ) {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Only check batch if job is actually part of a batch
        if ($this->batching() && optional($this->batch())->cancelled()) {
            Log::info('Skipping cancelled batch job', [
                'student_id' => $this->student->id
            ]);
            return;
        }

        try {
            Log::info('Starting PDF generation', [
                'student_id' => $this->student->id,
                'batch_id' => $this->batching() ? $this->batch()->id : null
            ]);

            $reportCardService = app(\App\Services\ReportCardService::class);

            $reportData = $reportCardService->generateReport(
                $this->student,
                $this->termId,
                $this->academicSessionId,
                $this->templateId
            );

            $signatures = $this->prepareSignatures($reportData);

            $fileName = str($this->student->admission_number . '-' . $this->student->full_name)->slug() . '.pdf';
            $filePath = $this->tempDir . '/' . $fileName;

            if (!File::exists(dirname($filePath))) {
                File::makeDirectory(dirname($filePath), 0755, true);
            }

            $pdf = Pdf::view('pdfs.term-report-card-pdf', [
                'report' => $reportData,
                'school' => $this->student->school,
                'student' => $this->student,
                'schoolLogo' => $this->schoolLogo,
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

            $pdf->save($filePath);

            if (!File::exists($filePath)) {
                throw new \Exception("Failed to create PDF at {$filePath}");
            }

            Log::info('PDF generation completed', [
                'student_id' => $this->student->id,
                'batch_id' => $this->batching() ? $this->batch()->id : null
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Generation Failed', [
                'student' => $this->student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
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
}