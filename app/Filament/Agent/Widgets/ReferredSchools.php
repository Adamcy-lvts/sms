<?php

namespace App\Filament\Agent\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Relations\HasMany;


class ReferredSchools extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $agent = auth()->user()->agent; // Assuming the authenticated user is linked to an agent

        return $table
            ->relationship(fn (): HasMany => $agent->schools())
            ->inverseRelationship('schools')
            ->columns([
                TextColumn::make('name')->label('School Name'),
                TextColumn::make('email')->label('Email'),
                TextColumn::make('created_at')->label('Referred At')->date(),
                TextColumn::make('schools.subscription')
                    ->label('Subscription Count')
                    ->getStateUsing(fn ($record) => $record->subscriptions()->count())
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === 0 => 'gray',
                        $state > 0 && $state <= 5 => 'warning',
                        $state > 5 => 'success',
                        default => 'secondary',
                    }),
            ]);
    }
}
