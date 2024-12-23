<!-- resources/views/components/current-academic-info.blade.php -->
<div class="mr-4 text-sm">
    @if($currentSession)
        <span class="font-medium">Session:</span> {{ $currentSession->name }}<br>
    @endif
    @if($currentTerm)
        <span class="font-medium">Term:</span> {{ $currentTerm->name }}
    @endif
</div>