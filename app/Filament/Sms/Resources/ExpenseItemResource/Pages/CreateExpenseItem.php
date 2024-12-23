<?php

namespace App\Filament\Sms\Resources\ExpenseItemResource\Pages;

use App\Filament\Sms\Resources\ExpenseItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseItem extends CreateRecord
{
    protected static string $resource = ExpenseItemResource::class;
}
