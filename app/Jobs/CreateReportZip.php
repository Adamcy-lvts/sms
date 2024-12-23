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


    public function __construct(
        protected string $reportBatchId,
        protected string $tempDir,
        protected int $userId
    ) {
        $this->onQueue('pdf-generation');
    }

    public function handle()
    {
        try {
            Log::info('Starting ZIP creation', [
                'batch_id' => $this->reportBatchId,
                'temp_dir' => $this->tempDir
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
}
