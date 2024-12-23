<?php

namespace App\Filament\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\Exporter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference')
                ->label('Payment Reference'),

            ExportColumn::make('student.full_name')
                ->label('Student Name'),

            ExportColumn::make('student.classRoom.name')
                ->label('Class'),

            ExportColumn::make('academicSession.name')
                ->label('Academic Session'),

            ExportColumn::make('term.name')
                ->label('Term'),

            // Payment Items as a concatenated string
            ExportColumn::make('payment_types')
                ->label('Payment Types')
                ->state(function (Payment $record): string {
                    return $record->paymentItems->map(function ($item) {
                        return "{$item->paymentType->name} (₦" . number_format($item->amount, 2) . ")";
                    })->join(', ');
                }),

            ExportColumn::make('amount')
                ->label('Total Amount')
                ->formatStateUsing(fn(float $state): string => '₦' . number_format($state, 2)),

            ExportColumn::make('deposit')
                ->label('Amount Paid')
                ->formatStateUsing(fn(float $state): string => '₦' . number_format($state, 2)),

            ExportColumn::make('balance')
                ->label('Balance')
                ->formatStateUsing(fn(float $state): string => '₦' . number_format($state, 2)),

            ExportColumn::make('status.name')
                ->label('Payment Status'),

            ExportColumn::make('paymentMethod.name')
                ->label('Payment Method'),

            ExportColumn::make('payer_name')
                ->label('Payer Name'),

            ExportColumn::make('payer_phone_number')
                ->label('Payer Phone'),

            ExportColumn::make('paid_at')
                ->label('Payment Date')
                ->formatStateUsing(fn($state) => $state?->format('j M, Y g:i A')),

            ExportColumn::make('due_date')
                ->label('Due Date')
                ->formatStateUsing(fn($state) => $state?->format('j M, Y g:i A')),

            ExportColumn::make('remark')
                ->label('Remark'),

            ExportColumn::make('createdByUser.name')
                ->label('Created By')
                ->formatStateUsing(fn($state) => $state ?? 'System'),

            // Optionally add updated by user
            ExportColumn::make('updatedByUser.name')
                ->label('Last Updated By')
                ->formatStateUsing(fn($state) => $state ?? 'System'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return "Your payment export of {$count} " . str('row')->plural($export->successful_rows) . " has completed and is ready to download.";
    }

    public function getFileName(Export $export): string
    {
        return "payments-{$export->getKey()}.csv";
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'student.classRoom',
            'academicSession',
            'term',
            'status',
            'paymentMethod',
            'paymentItems.paymentType',
            'createdByUser',
            'updatedByUser',
        ]);
    }
}
