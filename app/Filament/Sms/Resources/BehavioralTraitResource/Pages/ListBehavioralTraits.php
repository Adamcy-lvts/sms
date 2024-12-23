<?php

namespace App\Filament\Sms\Resources\BehavioralTraitResource\Pages;

use App\Filament\Sms\Resources\BehavioralTraitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBehavioralTraits extends ListRecords
{
    protected static string $resource = BehavioralTraitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
