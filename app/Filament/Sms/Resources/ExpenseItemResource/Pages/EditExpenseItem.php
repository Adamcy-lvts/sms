<?php

namespace App\Filament\Sms\Resources\ExpenseItemResource\Pages;

use App\Filament\Sms\Resources\ExpenseItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseItem extends EditRecord
{
    protected static string $resource = ExpenseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
