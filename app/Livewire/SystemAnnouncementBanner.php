<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SystemAnnouncement;

class SystemAnnouncementBanner extends Component
{
    public $announcement;

    protected $listeners = ['refreshAnnouncement'];

    public function mount()
    {
        $this->loadAnnouncement();
    }

    public function loadAnnouncement()
    {
        $this->announcement = SystemAnnouncement::active()
            ->whereDoesntHave('dismissals', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->first();
    }

    public function dismissAnnouncement($announcementId)
    {
        if ($this->announcement && $this->announcement->is_dismissible) {
            $this->announcement->dismissals()->create([
                'user_id' => auth()->id(),
                'dismissed_at' => now(),
            ]);
            
            $this->announcement = null;
        }
    }

    public function render()
    {
        return view('livewire.system-announcement-banner');
    }
}