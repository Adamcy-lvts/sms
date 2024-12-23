<?php

namespace App\Filament\Forms\Components\Concerns;

trait HasRtlSupport
{
    public function directionRTL(): static
    {
        $this->extraInputAttributes([
            'dir' => 'rtl',
            'style' => 'text-align: right; font-family: "Noto Naskh Arabic", Arial;',
        ]);

        return $this;
    }
}