<?php

namespace App\Filament\Sms\Resources\ExpenseResource\Pages;

use Filament\Actions;
use App\Models\ExpenseItem;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\ExpenseResource;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Group items by category
        // $itemsByCategory = collect($data['items'])->groupBy('expense_category_id');
        // Group items by category and add names
        $itemsByCategory = collect($data['items'])->map(function ($item) {
            $expenseItem = ExpenseItem::find($item['expense_item_id']);
            $item['name'] = $expenseItem->name;
            return $item;
        })->groupBy('expense_category_id');
        // dd($itemsByCategory);
        // Create expense records for each category
        $itemsByCategory->each(function ($items, $categoryId) use ($data) {
            $record = new ($this->getModel())([
                'expense_category_id' => $categoryId,
                'expense_items' => $items->toArray(),
                'amount' => $items->sum('amount'),
                'expense_date' => $data['expense_date'],
                'reference' => $data['reference'] . '-' . Str::random(4), // Make unique
                'status' => $data['status'],
                'payment_method' => $data['payment_method'],
                'receipt_number' => $data['receipt_number'] ?? null,
                'academic_session_id' => $data['academic_session_id'],
                'term_id' => $data['term_id'],
                'description' => $data['description'] ?? null,
                'recorded_by' => auth()->id(),
                'approved_by' => $data['status'] === 'approved' ? auth()->id() : null,
            ]);

            if (static::getResource()::isScopedToTenant() && ($tenant = Filament::getTenant())) {
                $this->associateRecordWithTenant($record, $tenant);
            } else {
                $record->save();
            }
        });

        // Return the first record to satisfy the method return type
        return ($this->getModel())::latest()->first();
    }
}
