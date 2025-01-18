{{-- resources/views/livewire/system-announcement-banner.blade.php --}}
<div>
    @if ($announcement)
        <div x-data="{ show: true }" x-show="show" style="width: 100%; border-bottom: 1px solid; 
            {{ $announcement->background_color ? "background-color: {$announcement->background_color};" : '' }} 
            {{ $announcement->text_color ? "color: {$announcement->text_color};" : '' }}
            @if (!$announcement->background_color)
                @if ($announcement->type === 'warning') background-color:rgb(221, 128, 14); border-color:rgb(238, 190, 128);
                @elseif ($announcement->type === 'danger') background-color:rgb(204, 30, 30); border-color: #fecaca;
                @else background-color:rgb(116, 170, 221); border-color:rgb(27, 60, 153);
                @endif
            @endif
            @if (!$announcement->text_color)
                @if ($announcement->type === 'warning') color:rgb(255, 255, 255);
                @elseif ($announcement->type === 'danger') color:rgb(255, 255, 255);
                @else color:rgb(6, 45, 95);
                @endif
            @endif">
            <div style="width: 100%; padding: 0.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        @if ($announcement->type === 'warning')
                            <x-heroicon-s-exclamation-triangle style="height: 1.25rem; width: 1.25rem; flex-shrink: 0; color:rgb(212, 205, 192);" />
                        @elseif($announcement->type === 'danger')
                            <x-heroicon-s-exclamation-circle style="height: 1.25rem; width: 1.25rem; flex-shrink: 0; color: #ef4444;" />
                        @else
                            <x-heroicon-s-information-circle style="height: 1.25rem; width: 1.25rem; flex-shrink: 0; color:rgb(16, 131, 185);" />
                        @endif

                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                            <span style="font-weight: 500;">{{ $announcement->title }}</span>
                            <span>{!! $announcement->message !!}</span>
                        </div>
                    </div>

                    @if ($announcement->is_dismissible)
                        <button type="button"
                            x-on:click="show = false; $wire.dismissAnnouncement({{ $announcement->id }})"
                            style="flex-shrink: 0; padding: 0.25rem; border-radius: 0.5rem;">
                            <x-heroicon-s-x-mark style="height: 1.25rem; width: 1.25rem;" />
                            <span style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0;">Dismiss</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
