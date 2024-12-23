<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Sms\Resources\ClassRoomResource;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\ClassRoomStatsOverview;

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
