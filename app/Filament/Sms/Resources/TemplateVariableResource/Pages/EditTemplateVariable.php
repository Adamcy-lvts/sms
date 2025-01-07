<?php

namespace App\Filament\Sms\Resources\TemplateVariableResource\Pages;

use App\Filament\Sms\Resources\TemplateVariableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemplateVariable extends EditRecord
{
    protected static string $resource = TemplateVariableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
