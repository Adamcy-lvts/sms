<?php

namespace App\Filament\Sms\Resources;

use App\Filament\Sms\Resources\ExpenseItemResource\Pages;
use App\Filament\Sms\Resources\ExpenseItemResource\RelationManagers;
use App\Models\ExpenseItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseItemResource extends Resource
{
    protected static ?string $model = ExpenseItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Expense Items';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),

                Forms\Components\TextInput::make('unit')
                    ->required(),

                Forms\Components\TextInput::make('default_amount')
                    ->numeric()
                    ->prefix('₦')
                    ->required(),

                Forms\Components\Toggle::make('is_stock_tracked')
                    ->default(true)
                    ->reactive()
                    ->afterStateUpdated(fn($state, callable $set) =>
                    $state ? null : $set('minimum_quantity', 0)),

                Forms\Components\TextInput::make('minimum_quantity')
                    ->numeric()
                    ->default(0)
                    ->visible(fn(callable $get) => $get('is_stock_tracked')),

                Forms\Components\TextInput::make('current_stock')
                    ->numeric()
                    ->default(0)
                    ->visible(fn(callable $get) => $get('is_stock_tracked')),

                Forms\Components\KeyValue::make('specifications')
                    ->keyLabel('Property')
                    ->valueLabel('Value')
                    ->reorderable(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('category.name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit')
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_amount')
                    ->money('NGN')
                    ->sortable(),

                // Tables\Columns\IconColumn::make('is_stock_tracked')
                //     ->boolean()
                //     ->label('Stock Tracked'),

                // Tables\Columns\TextColumn::make('current_stock')
                //     ->sortable(),
                //     // ->visible(fn($record) => $record->is_stock_tracked),

                // Tables\Columns\TextColumn::make('minimum_quantity')
                //     ->label('Min Qty'),
                    // ->visible(fn($record) => $record->is_stock_tracked),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_stock_tracked')
                    ->label('Stock Tracked'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
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
            'index' => Pages\ListExpenseItems::route('/'),
            'create' => Pages\CreateExpenseItem::route('/create'),
            'edit' => Pages\EditExpenseItem::route('/{record}/edit'),
        ];
    }
}
