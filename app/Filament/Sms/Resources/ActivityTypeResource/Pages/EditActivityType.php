<?php

namespace App\Filament\Sms\Resources\ActivityTypeResource\Pages;

use App\Filament\Sms\Resources\ActivityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityType extends EditRecord
{
    protected static string $resource = ActivityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
