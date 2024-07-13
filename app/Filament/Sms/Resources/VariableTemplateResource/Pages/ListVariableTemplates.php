<?php

namespace App\Filament\Sms\Resources\VariableTemplateResource\Pages;

use App\Filament\Sms\Resources\VariableTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVariableTemplates extends ListRecords
{
    protected static string $resource = VariableTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
