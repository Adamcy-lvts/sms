<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\GradingScale;
use Filament\Resources\Resource;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\GradingScaleResource\Pages;
use App\Filament\Sms\Resources\GradingScaleResource\RelationManagers;

class GradingScaleResource extends Resource
{
    protected static ?string $model = GradingScale::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Grading Scale Details')
                ->description('Define grade ranges and their corresponding remarks')
                ->schema([
                    TextInput::make('grade')
                        ->required()
                        ->maxLength(5)
                        ->hint('Letter grade (e.g., A, B+, C)'),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            TextInput::make('min_score')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->label('Minimum Score')
                                ->suffix('%'),

                            TextInput::make('max_score')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->label('Maximum Score')
                                ->suffix('%')
                                ->rules([
                                    fn(Forms\Get $get): string => 'gte:' . $get('min_score'),
                                ]),
                        ]),

                    TextInput::make('remark')
                        ->maxLength(50)
                        ->hint('Brief description of this grade (e.g., Excellent, Good)')
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grade')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('score_range')
                    ->label('Score Range')
                    ->state(function (GradingScale $record): string {
                        return "{$record->min_score} - {$record->max_score}%";
                    }),


                TextColumn::make('remark')
                    ->sortable()
                    ->searchable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('min_score', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Statuses')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGradingScales::route('/'),
            'create' => Pages\CreateGradingScale::route('/create'),
            // 'edit' => Pages\EditGradingScale::route('/{record}/edit'),
        ];
    }
}
