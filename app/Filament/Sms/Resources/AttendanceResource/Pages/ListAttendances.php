<?php

namespace App\Filament\Sms\Resources\AttendanceResource\Pages;

use App\Filament\Sms\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            // Actions\Action::make('takeAttendance')
            // ->label('Take Attendance')
            // ->icon('heroicon-m-clipboard-document-check')
            // ->url(fn(): string => static::getUrl(['attendance']))
            // ->color('primary'),
        ];
    }

}
