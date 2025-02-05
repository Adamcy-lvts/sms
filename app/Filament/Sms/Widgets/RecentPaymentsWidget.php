<?php

namespace App\Filament\Sms\Widgets;

use App\Models\Payment;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->where('school_id', Filament::getTenant()->id)
                    ->latest('paid_at')
                    ->limit(5)
            )
            ->columns([
                // Start with just basic columns

                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(['first_name', 'last_name'])
                    ->formatStateUsing(fn($record) => sprintf(
                        "%s %s",
                        $record?->student?->first_name ?? '',
                        $record?->student?->last_name ?? ''
                    ) ?: 'N/A'),

                TextColumn::make('classRoom.name')
                    ->label('Class Room')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => $state ?? 'Unassigned'),

                TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ?? '-'),

                TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),

                TextColumn::make('paymentItems.paymentType.name')
                    ->label('Payment Types')
                    ->formatStateUsing(function ($record) {
                        return $record?->paymentItems
                            ->map(fn($item) => $item->paymentType?->name ?? 'Unknown')
                            ->filter()
                            ->join(', ') ?: 'No Items';
                    })
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->formatStateUsing(fn($state) => formatNaira($state ?? 0))
                    ->sortable(),

                TextColumn::make('deposit')
                    ->formatStateUsing(fn($state) => formatNaira($state))
                    ->sortable(),

                TextColumn::make('balance')
                    ->formatStateUsing(fn($state) => formatNaira($state))
                    ->sortable(),

                TextColumn::make('status.name')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        $label = $state;
                        if ($record->is_balance_payment) {
                            $label .= ' (Balance Payment)';
                        }
                        return $label;
                    })
                    ->color(fn(string $state, $record): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('paymentMethod.name')
                    ->label('Method')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->dateTime(),
            ])
            ->actions([])
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['accountant', 'bursar', 'financial_manager']);
    }
}
