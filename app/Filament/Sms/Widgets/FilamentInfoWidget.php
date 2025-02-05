<?php

namespace App\Filament\Sms\Widgets;

use Filament\Widgets\Widget;

class FilamentInfoWidget extends Widget
{
    protected static string $view = 'filament.sms.widgets.filament-info-widget';
    protected static ?int $sort = 0;
    public $user;
    public $school;
    public $isAdmin;

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->school = $this->user->schools->first();
        $this->isAdmin = $this->user->hasRole(['super_admin', 'admin', 'principal', 'vice_principal']);
    }
}
