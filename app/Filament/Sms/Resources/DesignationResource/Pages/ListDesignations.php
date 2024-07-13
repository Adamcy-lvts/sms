<?php

namespace App\Filament\Sms\Resources\DesignationResource\Pages;

use App\Filament\Sms\Resources\DesignationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDesignations extends ListRecords
{
    protected static string $resource = DesignationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
