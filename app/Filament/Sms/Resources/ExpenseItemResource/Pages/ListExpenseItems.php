<?php

namespace App\Filament\Sms\Resources\ExpenseItemResource\Pages;

use App\Filament\Sms\Resources\ExpenseItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseItems extends ListRecords
{
    protected static string $resource = ExpenseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
