<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class ReportProgress extends Component
{
    public ?string $batchId = null;
    public $progress = 0;
    public $status = '';
    public $totalJobs = 0;
    public $processedJobs = 0;
    public $failedJobs = 0;
    public $downloadUrl = null;
    


    public function mount()
    {
        // Try to get batch ID from cache
        $this->batchId = Cache::get('report_batch_id_' . auth()->id());
        if ($this->batchId) {
            $this->checkProgress();
        }
    }

    public function checkProgress()
    {
        if (!$this->batchId) {
            return;
        }

        try {
            $batch = Bus::findBatch($this->batchId);

            if (!$batch) {
                $this->status = 'error';
                return;
            }

            $this->progress = $batch->progress();
            $this->totalJobs = $batch->totalJobs;
            $this->processedJobs = $batch->processedJobs();
            $this->failedJobs = $batch->failedJobs;

            if ($batch->finished()) {
                $this->status = 'completed';

                // Check if zip file exists
                $zipFile = "report-cards-{$this->batchId}.zip";
                if (Storage::disk('public')->exists("reports/bulk/{$zipFile}")) {
                    $this->downloadUrl = Storage::url("reports/bulk/{$zipFile}");
                }

                // Clear batch ID from cache when complete
                Cache::forget('report_batch_id_' . auth()->id());
            } elseif ($batch->cancelled()) {
                $this->status = 'cancelled';
                Cache::forget('report_batch_id_' . auth()->id());
            } else {
                $this->status = 'processing';
              
                
            }
        } catch (\Exception $e) {
            Log::error('Error checking batch progress', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage()
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
