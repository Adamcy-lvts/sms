<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PaymentType;
use Faker\Provider\ar_EG\Text;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\PaymentTypeResource\Pages;
use App\Filament\Sms\Resources\PaymentTypeResource\RelationManagers;

class PaymentTypeResource extends Resource
{
    protected static ?string $model = PaymentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Financial Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Main Information Section
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('category')
                        ->options([
                            'service_fee' => 'Service Fee',
                            'physical_item' => 'Physical Item',
                        ])
                        ->default('service_fee')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (string $state, Forms\Set $set) { // Fixed callback arguments
                            $set('inventory_enabled', $state === 'physical_item');
                        }),

                    Forms\Components\Toggle::make('active')
                        ->label('Is Active')
                        ->default(true),

                    Forms\Components\Toggle::make('is_tuition')
                        ->label('Is Tuition Fee')
                        ->default(false)
                        ->live(),

                    Forms\Components\Select::make('class_level')
                        ->label('Class Level')
                        ->options([
                            'nursery' => 'Nursery',
                            'primary' => 'Primary',
                            'secondary' => 'Secondary',
                            'all' => 'All Levels',
                        ])
                        ->visible(fn (Forms\Get $get) => 
                            $get('is_tuition') && $get('category') === 'service_fee'
                        ),

                    Forms\Components\Toggle::make('installment_allowed')
                        ->label('Allow Installment Payments')
                        ->default(false)
                        ->live()
                        ->visible(fn (Forms\Get $get) => 
                            $get('category') === 'service_fee'
                        ),

                    Forms\Components\TextInput::make('min_installment_amount')
                        ->label('Minimum Installment Amount')
                        ->numeric()
                        ->prefix('₦')
                        ->visible(fn (Forms\Get $get) => 
                            $get('installment_allowed') && $get('category') === 'service_fee'
                        ),

                    Forms\Components\Textarea::make('description')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])->columns(3),

            // Price Section
            Forms\Components\Section::make('Pricing Details')
                ->schema([
                    // For Service Fees
                    Forms\Components\TextInput::make('amount')
                        ->label('Fixed Amount')
                        ->numeric()
                        ->prefix('₦')
                        ->visible(fn(Forms\Get $get) => $get('category') === 'service_fee'),

                    // For Physical Items
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Unit Cost Price')
                                ->helperText('Cost price per unit')
                                ->numeric()
                                ->prefix('₦')
                                ->required(),

                            Forms\Components\TextInput::make('selling_price')
                                ->label('Selling Price')
                                ->helperText('Price charged to students')
                                ->numeric()
                                ->prefix('₦')
                                ->required(),

                            Forms\Components\TextInput::make('initial_stock')
                                ->label('Initial Stock')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->helperText('Initial quantity in stock'),

                            Forms\Components\TextInput::make('reorder_level')
                                ->label('Reorder Level')
                                ->numeric()
                                ->default(10)
                                ->minValue(1)
                                ->helperText('Stock level to trigger reorder alert'),
                        ])
                        ->visible(fn(Forms\Get $get) => $get('category') === 'physical_item')
                        ->columns(2),
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

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'primary' => 'service_fee',
                        'success' => 'physical_item',
                    ]),

                Tables\Columns\IconColumn::make('is_tuition')
                    ->boolean()
                    ->label('Tuition'),

                Tables\Columns\TextColumn::make('class_level')
                    ->badge()
                    ->colors([
                        'warning' => 'nursery',
                        'cyan' => 'primary',
                        'indigo' => 'secondary',
                        'gray' => 'all',
                    ]),

                Tables\Columns\ToggleColumn::make('installment_allowed')
                    ->label('Installments'),

                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(
                        fn($state, Model $record) =>
                        $record->category === 'physical_item'
                            ? formatNaira($record->inventory?->selling_price ?? 0, 2)
                            : formatNaira($state ?? 0, 2)
                    )
                    ->sortable(),

                // Only show for physical items
                Tables\Columns\TextColumn::make('inventory.quantity')
                    ->label('Stock')
                    ->visible(fn($livewire) => $livewire instanceof Pages\ListPaymentTypes)
                    ->badge()
                    ->formatStateUsing(
                        fn($state, Model $record) =>
                        $record->category === 'physical_item' ? ($state ?? 0) : '-'
                    )
                    ->color(
                        fn($state, Model $record) =>
                        $record->category === 'physical_item'
                            ? ($state > ($record->inventory?->reorder_level ?? 0) ? 'success' : 'danger')
                            : 'gray'
                    ),

                Tables\Columns\ToggleColumn::make('active')
                    ->sortable(),

                // has_due_date column
                Tables\Columns\ToggleColumn::make('has_due_date')
                    ->label('Due Date')
                    ->sortable(),

              
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'service_fee' => 'Service Fee',
                        'physical_item' => 'Physical Item',
                    ]),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->placeholder('All'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (PaymentType $record) {
                            // First delete inventory if exists
                            if ($record->inventory) {
                                $record->inventory->delete();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Payment type deleted')
                                ->body('The payment type has been deleted successfully.')
                        )
                ]),
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
            'index' => Pages\ListPaymentTypes::route('/'),
            'create' => Pages\CreatePaymentType::route('/create'),
            'edit' => Pages\EditPaymentType::route('/{record}/edit'),
        ];
    }
}
