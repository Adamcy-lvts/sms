<?php

namespace App\Filament\Sms\Resources\BookResource\Pages;

use App\Filament\Sms\Resources\BookResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBook extends CreateRecord
{
    protected static string $resource = BookResource::class;
}
