{{-- resources/views/filament/components/bulk-report-progress.blade.php --}}
<div class="p-4">
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium">{{ $currentStep }}</span>
            <span class="text-sm font-medium">{{ number_format($progress, 0) }}%</span>
        </div>
        
        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
            <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-500" 
                 style="width: {{ $progress }}%"></div>
        </div>
    </div>
</div>