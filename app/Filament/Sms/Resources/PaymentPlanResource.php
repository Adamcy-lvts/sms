<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PaymentPlan;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\PaymentPlanResource\Pages;
use App\Filament\Sms\Resources\PaymentPlanResource\RelationManagers;

class PaymentPlanResource extends Resource
{
    protected static ?string $model = PaymentPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Financial Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payment_type_id')
                    ->label('Payment Type')
                    ->helperText('Select the type of payment this plan belongs to')
                    ->placeholder('Select a payment type - e.g., School Fees')
                    ->options(function () {
                        return \App\Models\PaymentType::where('school_id', Filament::getTenant()->id)
                            ->where('is_tuition', true)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $paymentType = \App\Models\PaymentType::find($state);
                            if ($paymentType) {
                                $set('session_amount', $paymentType->amount);
                                $set('term_amount', (int)($paymentType->amount / 3));
                            }
                        }
                    }),

                Forms\Components\TextInput::make('name')
                    ->helperText('A unique name to identify this payment plan')
                    ->placeholder('e.g., Primary School Fees Payment Plan')
                    ->required()
                    ->maxLength(255)
                    ->default(function (Forms\Get $get) {
                        $paymentType = \App\Models\PaymentType::find($get('payment_type_id'));
                        if (!$paymentType) return '';
                        return $paymentType->name . ' Payment Plan';
                    }),

                Forms\Components\Select::make('class_level')
                    ->label('Class Level')
                    ->helperText('The school level this payment plan applies to')
                    ->placeholder('Select a class level - e.g., Primary')
                    ->options([
                        'nursery' => 'Nursery',
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                    ])
                    ->required(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('term_amount')
                            ->label('Term Amount')
                            ->helperText('Amount to be paid per term')
                            ->placeholder('50000')
                            ->required()
                            ->numeric()
                            ->prefix('₦')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('session_amount', $state * 3);
                                }
                            }),

                        Forms\Components\TextInput::make('session_amount')
                            ->label('Session Amount')
                            ->helperText('Amount for entire session. Enter this or term amount')
                            ->placeholder('150000')
                            ->required()
                            ->numeric()
                            ->prefix('₦')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('term_amount', (int)($state / 3));
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('paymentType.name')
                    ->label('Payment Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('class_level')
                    ->label('Class Level')
                    ->badge()
                    ->colors([
                        'warning' => 'nursery',
                        'info' => 'primary',
                        'cyan' => 'secondary',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('term_amount')
                    ->money('NGN')
                    ->label('Term Amount')
                    ->sortable(),

                Tables\Columns\TextColumn::make('session_amount')
                    ->money('NGN')
                    ->label('Session Amount')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_level')
                    ->options([
                        'nursery' => 'Nursery',
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                    ]),
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
            'index' => Pages\ListPaymentPlans::route('/'),
            'create' => Pages\CreatePaymentPlan::route('/create'),
            'edit' => Pages\EditPaymentPlan::route('/{record}/edit'),
        ];
    }
}
