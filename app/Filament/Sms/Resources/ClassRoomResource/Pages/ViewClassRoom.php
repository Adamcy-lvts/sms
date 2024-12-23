<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Pages;

use App\Filament\Sms\Resources\ClassRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClassRoom extends ViewRecord
{
   protected static string $resource = ClassRoomResource::class;
   
   protected function getHeaderWidgets(): array
   {
       return [
           ClassRoomResource\Widgets\ClassRoomStatsOverview::class,
       ];
   }

   protected function getFooterWidgets(): array
   {
       return [
           ClassRoomResource\Widgets\TeachersTable::class,
           ClassRoomResource\Widgets\SubjectsTable::class,
           ClassRoomResource\Widgets\BooksTable::class
       ];
   }
}
