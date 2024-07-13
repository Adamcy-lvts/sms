<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Pages;

use App\Filament\Sms\Resources\ClassRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassRooms extends ListRecords
{
    protected static string $resource = ClassRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
