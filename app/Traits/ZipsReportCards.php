<?php

namespace App\Traits;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

trait ZipsReportCards
{
    protected function createReportCardsZip(array $pdfPaths, string $batchId, string $folderName): ?string 
    {
        try {
            // Create zip filename with descriptive name
            $zipFileName = sprintf(
                'report_cards_%s_%s_%s.zip',
                $folderName,
                date('Ymd_His'),
                $batchId
            );
    
            // Define paths
            $zipPath = "public/reports/bulk/{$folderName}/{$zipFileName}";
            $zipFullPath = storage_path("app/{$zipPath}");
    
            // Create directory if it doesn't exist
            $directory = dirname($zipFullPath);
            if (!file_exists($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new \Exception("Failed to create directory: {$directory}");
                }
            }
    
            // Create zip file
            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Failed to create zip file: {$zipFullPath}");
            }
    
            // Add files to zip
            foreach ($pdfPaths as $pdfPath) {
                if (Storage::exists($pdfPath)) {
                    $filename = basename($pdfPath);
                    $zip->addFile(Storage::path($pdfPath), $filename);
                } else {
                    Log::warning("PDF file not found: {$pdfPath}");
                }
            }
    
            $zip->close();
    
            // Verify zip was created
            if (!file_exists($zipFullPath)) {
                throw new \Exception("Zip file was not created at: {$zipFullPath}");
            }
    
            // Store URL with folder name identifier
            $cacheKey = "report_zip_url_{$folderName}_{$batchId}";
            Cache::put($cacheKey, Storage::url($zipPath), now()->addDay());
    
            return $zipPath;
    
        } catch (\Exception $e) {
            Log::error('ZIP creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'batch_id' => $batchId,
                'folder_name' => $folderName,
                'pdf_count' => count($pdfPaths)
            ]);
    
            return null;
        }
    }

    protected function getDownloadUrl(string $zipPath): string
    {
        // Convert storage path to public URL
        return Storage::url(str_replace('public/', '', $zipPath));
    }

    protected function cleanupPdfs(array $pdfPaths): void
    {
        foreach ($pdfPaths as $path) {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }
}
