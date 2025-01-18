<?php

namespace App\Filament\Resources\SystemAnnouncementResource\Pages;

use App\Filament\Resources\SystemAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSystemAnnouncement extends EditRecord
{
    protected static string $resource = SystemAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
