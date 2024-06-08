<?php

namespace App\Filament\Sms\Widgets;

use Filament\Widgets\Widget;

class FilamentInfoWidget extends Widget
{
    protected static string $view = 'filament.sms.widgets.filament-info-widget';


    public $user;
    public $school;

    public function mount(): void
    {
        $this->user = auth()->user();

        $this->school = $this->user->schools->first();
    }
}
