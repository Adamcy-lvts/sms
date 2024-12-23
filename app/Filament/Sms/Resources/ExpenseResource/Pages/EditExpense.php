<?php

namespace App\Filament\Sms\Resources\ExpenseResource\Pages;

use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\ExpenseResource;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Override the form's data before it loads
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the expense items and format them for the repeater
        $items = collect($this->record->expense_items)->map(function ($item) {
            return [
                'expense_category_id' => $this->record->expense_category_id,
                'expense_item_id' => $item['expense_item_id'] ?? null,
                'unit_price' => $item['unit_price'] ?? 0,
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? '',
                'amount' => $item['amount'] ?? 0,
                'description' => $item['description'] ?? '',
            ];
        })->toArray();

        // Calculate totals for summary fields
        $data['total_items'] = count($items);
        $data['total_quantity'] = collect($items)->sum('quantity');
        $data['total_amount'] = collect($items)->sum('amount');
        $data['items'] = $items;
        // dd($data);
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $itemsByCategory = collect($data['items'])->groupBy('expense_category_id');

        // Delete old records with same reference prefix except current
        ($this->getModel())::where('reference', 'like', explode('-', $record->reference)[0] . '%')
            ->where('id', '!=', $record->id)
            ->delete();

        // Create new records for each category
        $itemsByCategory->each(function ($items, $categoryId) use ($data, $record) {
            if ($record->expense_category_id == $categoryId) {
                // Update existing record
                $record->update([
                    'expense_items' => $items->toArray(),
                    'amount' => $items->sum('amount'),
                    'expense_date' => $data['expense_date'],
                    'status' => $data['status'],
                    'payment_method' => $data['payment_method'],
                    'receipt_number' => $data['receipt_number'] ?? null,
                    'academic_session_id' => $data['academic_session_id'],
                    'term_id' => $data['term_id'],
                    'description' => $data['description'] ?? null,
                    'approved_by' => $data['status'] === 'approved' ? auth()->id() : null,
                ]);
            } else {
                // Create new record for different category
                $newRecord = new ($this->getModel())([
                    'expense_category_id' => $categoryId,
                    'expense_items' => $items->toArray(),
                    'amount' => $items->sum('amount'),
                    'expense_date' => $data['expense_date'],
                    'reference' => explode('-', $record->reference)[0] . '-' . Str::random(4),
                    'status' => $data['status'],
                    'payment_method' => $data['payment_method'],
                    'receipt_number' => $data['receipt_number'] ?? null,
                    'academic_session_id' => $data['academic_session_id'],
                    'term_id' => $data['term_id'],
                    'description' => $data['description'] ?? null,
                    'recorded_by' => $record->recorded_by,
                    'approved_by' => $data['status'] === 'approved' ? auth()->id() : null,
                ]);

                if (static::getResource()::isScopedToTenant() && ($tenant = Filament::getTenant())) {
                    $this->associateRecordWithTenant($newRecord, $tenant);
                } else {
                    $newRecord->save();
                }
            }
        });

        return $record;
    }
}
