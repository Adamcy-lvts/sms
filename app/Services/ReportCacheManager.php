<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ReportCacheManager
{
    protected const CACHE_KEYS = [
        'batch_id' => 'report_batch_id_%d_%d',
        'progress' => 'report_progress_%s',
        'download_url' => 'report_zip_url_%s',
    ];

    public function generateUniqueKey(int $userId, int $schoolId, string $classId): string {
        // Include class ID in the key to avoid collisions
        return sprintf(
            'report_batch_%d_%d_%s_%s',
            $userId,
            $schoolId, 
            $classId,
            Str::random(8) // Add random component
        );
    }

    public function clearOldCaches(int $userId, int $schoolId): void {
        // Find and clear old caches with pattern matching
        $pattern = "report_batch_{$userId}_{$schoolId}_*";
        foreach (Cache::get($pattern) ?? [] as $key) {
            Cache::forget($key);
        }
    }

    public function clearAllCaches(int $userId, int $schoolId): void
    {
        try {
            // Get current batch info first
            $batchInfo = $this->getBatchInfo($userId, $schoolId);

            if ($batchInfo) {
                $batchId = $batchInfo['id'];

                // Clear all related caches
                foreach (self::CACHE_KEYS as $type => $pattern) {
                    $key = $this->formatCacheKey($type, $userId, $schoolId, $batchId);
                    Cache::forget($key);

                    Log::debug("Cleared cache: $type", ['key' => $key]);
                }

                // Delete physical files
                $this->deleteReportFiles($batchId);
            }

            Log::info('Cache cleanup completed', [
                'user_id' => $userId,
                'school_id' => $schoolId,
                'batch_id' => $batchId ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Cache cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function getBatchInfo(int $userId, int $schoolId): ?array
    {
        $key = sprintf(self::CACHE_KEYS['batch_id'], $userId, $schoolId);
        return Cache::get($key);
    }

    protected function formatCacheKey(string $type, int $userId, int $schoolId, string $batchId = null): string
    {
        return match ($type) {
            'batch_id' => sprintf(self::CACHE_KEYS['batch_id'], $userId, $schoolId),
            'progress' => sprintf(self::CACHE_KEYS['progress'], $batchId),
            'download_url' => sprintf(self::CACHE_KEYS['download_url'], $batchId),
            default => throw new \InvalidArgumentException("Unknown cache type: $type")
        };
    }

    protected function deleteReportFiles(string $batchId): void
    {
        try {
            // Directory for bulk reports
            $directory = 'public/reports/bulk';
            $pattern = "report_cards_*_{$batchId}.zip";

            // Get all files in directory
            $files = Storage::files($directory);

            // Filter files matching our pattern
            $matchingFiles = array_filter($files, function ($file) use ($pattern) {
                return fnmatch($pattern, basename($file));
            });

            // Delete matching files
            foreach ($matchingFiles as $file) {
                Storage::delete($file);
                Log::debug("Deleted file: $file");
            }

            // Optionally clean up individual PDF files
            $pdfDirectory = "reports";
            if (Storage::exists($pdfDirectory)) {
                $pdfFiles = Storage::allFiles($pdfDirectory);
                foreach ($pdfFiles as $file) {
                    if (str_ends_with($file, '.pdf')) {
                        Storage::delete($file);
                        Log::debug("Deleted PDF: $file");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete report files', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
