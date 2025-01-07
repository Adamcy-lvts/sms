<?php

namespace App\Filament\Sms\Resources\TemplateVariableResource\Pages;

use App\Filament\Sms\Resources\TemplateVariableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemplateVariables extends ListRecords
{
    protected static string $resource = TemplateVariableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
