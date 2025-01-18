<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ClassRoom;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ReportProgress extends Component
{
    public ?string $batchId = null;
    public $progress = 0;
    public $status = '';
    public $totalJobs = 0;
    public $processedJobs = 0;
    public $failedJobs = 0;
    public $downloadUrl = null;
    public $tenant;
    public $totalStudents = 0;
    public $processedStudents = 0;
    public $className = '';

    public function mount()
    {
        $tenant = Filament::getTenant();
        $this->tenant = $tenant;
        
        // Get batch info including class name
        $batchInfoKey = "report_batch_info_" . auth()->id() . "_" . $tenant->id;
        $batchInfo = Cache::get($batchInfoKey);
        $this->className = $batchInfo['class_name'] ?? '';

        $cacheKey = "report_batch_id_" . auth()->id() . "_" . $tenant->id;

        // Get batch info from cache and extract just the ID
        $batchInfo = Cache::get($cacheKey);
        $this->batchId = $batchInfo['id'] ?? null;
        $this->totalStudents = $batchInfo['total_students'] ?? 0;

        if ($this->batchId) {
            $this->checkProgress();
        }
    }

    public function getProgressPercentageProperty()
    {
        if ($this->totalStudents <= 0) {
            return 0;
        }
        // Calculate percentage based on processed students
        return min(100, round(($this->processedStudents / $this->totalStudents) * 100));
    }

    public function checkProgress()
    {
        if (!$this->batchId) {
            return;
        }

        try {
            $batch = Bus::findBatch($this->batchId);

            if (!$batch) {
                Log::error('Batch not found', ['batch_id' => $this->batchId]);
                $this->status = 'error';
                return;
            }

            $batchInfoKey = "batch_info_" . auth()->id() . "_" . $this->tenant->id;
            $batchInfo = Cache::get($batchInfoKey);

            if ($batch->finished()) {
                $this->status = 'completed';

                if ($batchInfo && isset($batchInfo['folder_name'])) {
                    $folderName = $batchInfo['folder_name'];
                    // Use the descriptive folder name for cache key
                    $cacheKey = "report_zip_url_{$folderName}_{$this->batchId}";
                    $this->downloadUrl = Cache::get($cacheKey);
                }

                Cache::forget("report_batch_id_" . auth()->id() . "_" . $this->tenant->id);
            } elseif ($batch->cancelled()) {
                $this->status = 'cancelled';
                Cache::forget('report_batch_id_' . auth()->id() . "_" . $this->tenant->id);
            } else {
                $this->status = 'processing';
            }

            // Update progress tracking
            $this->progress = $batch->progress();
            $this->totalJobs = 1;
            $this->processedJobs = $batch->processedJobs();
            $this->failedJobs = $batch->failedJobs;
            $this->processedStudents = Cache::get("report_progress_{$this->batchId}", 0);
        } catch (\Exception $e) {
            Log::error('Error in checkProgress', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->status = 'error';
        }
    }

    public function cancelGeneration()
    {
        try {
            $batch = Bus::findBatch($this->batchId);
            if ($batch) {
                $batch->cancel();
                $this->status = 'cancelled';
                Cache::forget('report_batch_id_' . auth()->id());

                Notification::make()
                    ->title('Report Generation Cancelled')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error cancelling batch', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage()
            ]);

            Notification::make()
                ->title('Error Cancelling Generation')
                ->danger()
                ->send();
        }
    }

    public function render()
    {

        return view('livewire.report-progress');
    }

    public function getProgressColorProperty()
    {
        return match ($this->status) {
            'completed' => 'success',
            'cancelled' => 'warning',
            'error' => 'danger',
            default => 'primary'
        };
    }

    public function getStatusTextProperty()
    {
        return match ($this->status) {
            'completed' => 'Generation Complete',
            'cancelled' => 'Generation Cancelled',
            'error' => 'Error Occurred',
            'processing' => 'Generating Reports...',
            default => 'Waiting to Start'
        };
    }
}
