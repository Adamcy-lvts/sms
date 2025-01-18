<?php

namespace App\Services;

use App\Models\ClassRoom;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerateReportCardsJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class BulkReportCardService
{
    protected $statusService;
    protected $cacheManager;

    public function __construct(StatusService $statusService, ReportCacheManager $cacheManager)
    {

        $this->statusService = $statusService;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Start bulk generation of report cards
     */
    public function startBulkGeneration(
        ClassRoom $classRoom,
        int $termId,
        int $sessionId,
        ?int $templateId = null
    ): string {
        try {

            // Clear all previous caches and files
            $this->cacheManager->clearAllCaches(
                auth()->id(),
                $classRoom->school->id
            );
            // Create batch first to get ID
            $batch = Bus::batch([])
                ->name('Generate Report Cards')
                ->allowFailures()
                ->dispatch();

            $batchId = $batch->id;

            // Get active students
            $activeStatusId = $this->statusService->getActiveStudentStatusId();
            $students = $classRoom->students()
                ->where('status_id', $activeStatusId)
                ->get();

            // Store batch info in cache with class ID
            $batchInfoKey = "batch_info_" . auth()->id() . "_" . Filament::getTenant()->id;
            Cache::put($batchInfoKey, [
                'id' => $batchId,
                'total_students' => $students->count(),
                'class_id' => $classRoom->id  // Store class ID
            ], now()->addHours(24));

            // Add job to batch with the correct batch ID
            $batch->add(new GenerateReportCardsJob(
                $students,
                $termId,
                $sessionId,
                $templateId,
                $batchId, // Pass the actual batch ID here
                $classRoom->school,
                $students->count()
            ));

            // Store batch info in cache
            $cacheKey = "report_batch_id_" . auth()->id() . "_" . $classRoom->school->id;
            Cache::put($cacheKey, [
                'id' => $batchId,
                'total_students' => $students->count()
            ], now()->addHours(24));

            return $batchId;
        } catch (\Exception $e) {
            Log::error('Failed to start bulk report generation', [
                'class_room_id' => $classRoom->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
