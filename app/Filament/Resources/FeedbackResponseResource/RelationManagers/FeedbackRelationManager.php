<?php

namespace App\Filament\Resources\FeedbackResponseResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

use Filament\Resources\RelationManagers\RelationManager;

class FeedbackRelationManager extends RelationManager
{
    protected static string $relationship = 'feedback';
    protected static ?string $title = 'Feedback Campaign';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Campaign Title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tracking.last_shown_at')
                    ->label('Last Shown')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true)),
            ])
            ->headerActions([
                // Add any header actions if needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Add any bulk actions if needed
            ]);
    }
}
