<?php

namespace App\Filament\Resources\FeedbackResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->sortable(),

                TextColumn::make('rating')
                    ->sortable(),

                TextColumn::make('comments')
                    ->limit(50),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name'),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
