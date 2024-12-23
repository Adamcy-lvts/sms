<?php

namespace App\Traits;

trait StyleBuilder
{
    protected function buildStyles(array $styles): string
    {
        return collect($styles)
            ->filter()
            ->map(fn($value, $prop) => "{$prop}: {$value};")
            ->implode(' ');
    }
}