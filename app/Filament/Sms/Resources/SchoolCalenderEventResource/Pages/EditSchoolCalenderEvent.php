<?php

namespace App\Filament\Sms\Resources\SchoolCalenderEventResource\Pages;

use App\Filament\Sms\Resources\SchoolCalenderEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolCalenderEvent extends EditRecord
{
    protected static string $resource = SchoolCalenderEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
