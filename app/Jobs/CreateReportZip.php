<?php

namespace App\Jobs;

use ZipArchive;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Filament\Notifications\Actions\Action;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateReportZip implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    // Increase retry attempts
    public $tries = 5;

    // Add exponential backoff
    public $backoff = [10, 30, 60, 120, 300]; // 10s, 30s, 1m, 2m, 5m

    // Add timeout
    public $timeout = 300; // 5 minutes

    public function __construct(
        protected string $reportBatchId,
        protected string $tempDir,
        protected int $userId
    ) {}

    public function handle()
    {
        try {


            // Add delay to ensure all PDFs are written
            sleep(5);

            // Verify PDFs exist and are complete
            if (!$this->verifyPDFs()) {
                // Release back to queue with delay
                $this->release(30);
                return;
            }

            Log::info('Starting ZIP creation', [
                'batch_id' => $this->reportBatchId,
                'temp_dir' => $this->tempDir,
                'files_count' => count(File::files($this->tempDir))
            ]);

            // Ensure the bulk reports directory exists
            $bulkDir = storage_path('app/public/reports/bulk');
            if (!File::exists($bulkDir)) {
                File::makeDirectory($bulkDir, 0755, true);
            }

            $zipName = "report-cards-{$this->reportBatchId}.zip";
            $zipPath = storage_path("app/public/reports/bulk/{$zipName}");

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                Log::info('ZIP file created', ['path' => $zipPath]);

                // Check if temp directory exists and has files
                if (!File::exists($this->tempDir)) {
                    throw new \Exception("Temporary directory not found: {$this->tempDir}");
                }

                $files = File::files($this->tempDir);
                if (empty($files)) {
                    throw new \Exception("No files found in temporary directory: {$this->tempDir}");
                }

                // Add files to ZIP
                foreach ($files as $file) {
                    $zip->addFile($file->getPathname(), $file->getFilename());
                    Log::info('Added file to ZIP', ['filename' => $file->getFilename()]);
                }

                $zip->close();

                // Verify ZIP was created
                if (!File::exists($zipPath)) {
                    throw new \Exception("ZIP file was not created successfully at {$zipPath}");
                }

                Log::info('ZIP creation completed successfully', [
                    'zip_path' => $zipPath,
                    'file_count' => count($files)
                ]);

                try {
                    $user = User::find($this->userId);
                    if ($user) {
                        $downloadUrl = Storage::url("reports/bulk/{$zipName}");

                        Log::debug('Sending notification to user', [
                            'user_id' => $this->userId,
                            'download_url' => $downloadUrl
                        ]);

                        Notification::make()
                            ->title('Report Cards ZIP Ready')
                            ->success()
                            ->body('Your bulk report cards have been generated successfully.')
                            ->actions([
                                Action::make('download')
                                    ->label('Download ZIP')
                                    ->url($downloadUrl)
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->openUrlInNewTab()
                            ])
                            ->sendToDatabase($user);


                        Log::info('Notification sent successfully', ['user_id' => $this->userId]);
                    } else {
                        Log::warning('User not found for notification', ['user_id' => $this->userId]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send notification', [
                        'error' => $e->getMessage(),
                        'user_id' => $this->userId
                    ]);
                }


                // Clean up temp directory after successful ZIP creation
                if (File::exists($this->tempDir)) {
                    File::deleteDirectory($this->tempDir);
                    Log::info('Cleaned up temporary directory', ['dir' => $this->tempDir]);
                }
            } else {
                throw new \Exception("Could not create ZIP file at {$zipPath}");
            }
        } catch (\Exception $e) {
            Log::error('ZIP Creation Failed', [
                'batch_id' => $this->reportBatchId,
                'temp_dir' => $this->tempDir,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Notify user of failure
            if ($user = User::find($this->userId)) {
                Notification::make()
                    ->title('Report Cards Generation Failed')
                    ->danger()
                    ->body('There was an error generating your bulk report cards. Please try again.')
                    ->sendToDatabase($user);
            }

            // Clean up temp directory even if ZIP creation fails
            if (File::exists($this->tempDir)) {
                File::deleteDirectory($this->tempDir);
                Log::info('Cleaned up temporary directory after error', ['dir' => $this->tempDir]);
            }

            throw $e;
        }
    }

    protected function verifyPDFs(): bool
    {
        if (!File::exists($this->tempDir)) {
            Log::warning('Temp directory not found, will retry', [
                'dir' => $this->tempDir
            ]);
            return false;
        }

        $files = File::files($this->tempDir);

        if (empty($files)) {
            Log::warning('No PDF files found, will retry', [
                'dir' => $this->tempDir
            ]);
            return false;
        }

        // Verify each file is a complete PDF
        foreach ($files as $file) {
            if (!$this->isCompletePDF($file->getPathname())) {
                Log::warning('Incomplete PDF found, will retry', [
                    'file' => $file->getFilename()
                ]);
                return false;
            }
        }

        return true;
    }

    protected function isCompletePDF(string $path): bool
    {
        // Check if file is still being written
        if (filemtime($path) > time() - 5) {
            return false;
        }

        // Basic PDF verification
        $content = File::get($path);
        return str_contains($content, '%PDF-') && str_contains($content, '%%EOF');
    }
}
