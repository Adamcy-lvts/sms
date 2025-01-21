<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Inventory;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\InventoryResource\Pages;
use App\Filament\Sms\Resources\InventoryResource\RelationManagers;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Item Information')
                ->description('Basic details about the inventory item')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('code')
                                ->label('Item Code')
                                ->maxLength(50)
                                ->unique(ignoreRecord: true),
                        ]),

                    Forms\Components\Textarea::make('description')
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Stock Management')
                ->description('Manage stock levels and pricing')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('quantity')
                                ->label('Current Stock')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->minValue(0)
                                ->step(1),

                            Forms\Components\TextInput::make('reorder_level')
                                ->label('Reorder Level')
                                ->helperText('Alert when stock reaches this level')
                                ->numeric()
                                ->default(10)
                                ->required()
                                ->minValue(1),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Unit Cost')
                                ->helperText('Cost price per unit')
                                ->numeric()
                                ->prefix('₦')
                                ->required()
                                ->step(0.01),

                            Forms\Components\TextInput::make('selling_price')
                                ->label('Selling Price')
                                ->helperText('Price charged to students')
                                ->numeric()
                                ->prefix('₦')
                                ->required()
                                ->step(0.01),
                        ]),
                ]),

            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Grid::make(1)
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active Status')
                                ->helperText('Inactive items won\'t appear in payment forms')
                                ->default(true),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),


                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn(Model $record): string => $record->isLowStock() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Cost')
                    ->formatStateUsing(fn($state, Model $record) => formatNaira($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->formatStateUsing(fn($state, Model $record) => formatNaira($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_type')
                    ->relationship('paymentType', 'name'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereRaw('quantity <= reorder_level')
                    ),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->placeholder('All'),
            ])
            ->actions([
                // Stock Adjustment Action
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-m-arrow-path')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label('Adjustment Type')
                            ->options([
                                'IN' => 'Stock In',
                                'OUT' => 'Stock Out',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->required(),
                    ])
                    ->action(function (array $data, Model $record): void {
                        // Create transaction record
                        $record->transactions()->create([
                            'school_id' => Filament::getTenant()->id,
                            'type' => $data['type'],
                            'quantity' => $data['quantity'],
                            'note' => $data['note'],
                            'created_by' => auth()->id(),
                        ]);

                        // Update inventory quantity
                        $record->quantity += ($data['type'] === 'IN' ? $data['quantity'] : -$data['quantity']);
                        $record->save();
                    }),

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

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'paymentType.name'];
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
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
