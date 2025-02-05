<?php

namespace App\Filament\Resources\FeedbackResponseResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class FeedbackTrackingRelationManager extends RelationManager
{
    protected static string $relationship = 'tracking';
    protected static ?string $title = 'Tracking History';
    protected static ?string $modelLabel = 'tracking record';
    protected static ?string $pluralModelLabel = 'tracking records';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('school_id')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\DateTimePicker::make('last_shown_at')
                    ->label('Last Shown')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('feedback.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_shown_at')
                    ->label('Last Shown')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('feedback.frequency_days')
                    ->label('Frequency (Days)')
                    ->sortable(),

                Tables\Columns\IconColumn::make('feedback.is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('last_shown_at')
                    ->form([
                        Forms\Components\DatePicker::make('shown_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('shown_until')
                            ->label('Until'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['shown_from'] && ! $data['shown_until']) {
                            return null;
                        }
                        
                        return 'Shown between ' . ($data['shown_from'] ?? 'any') . ' and ' . ($data['shown_until'] ?? 'any');
                    })
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['shown_from'],
                                fn($query, $date) => $query->whereDate('last_shown_at', '>=', $date),
                            )
                            ->when(
                                $data['shown_until'],
                                fn($query, $date) => $query->whereDate('last_shown_at', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Campaign Status')
                    ->queries(
                        true: fn ($query) => $query->whereHas('feedback', fn($q) => $q->where('is_active', true)),
                        false: fn ($query) => $query->whereHas('feedback', fn($q) => $q->where('is_active', false)),
                        blank: fn ($query) => $query
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('last_shown_at', 'desc')
            ->heading('Feedback Tracking History')
            ->description('View when feedback was shown to schools');
    }
}
