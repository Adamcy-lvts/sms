<?php

namespace App\Filament\Sms\Resources\ExpenseCategoryResource\Pages;

use App\Filament\Sms\Resources\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseCategory extends CreateRecord
{
    protected static string $resource = ExpenseCategoryResource::class;
}
