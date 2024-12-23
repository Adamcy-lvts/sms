<?php

namespace App\Filament\Sms\Resources\ActivityTypeResource\Pages;

use App\Filament\Sms\Resources\ActivityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityTypes extends ListRecords
{
    protected static string $resource = ActivityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
