<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubsPaymentResource\Pages;
use App\Filament\Resources\SubsPaymentResource\RelationManagers;
use App\Models\SubsPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubsPaymentResource extends Resource
{
    protected static ?string $model = SubsPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Payments';


    public static function getModelLabel(): string
    {
        return 'Payments';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('school_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('subscription_id')
                    ->numeric(),
                Forms\Components\TextInput::make('agent_id')
                    ->numeric(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('net_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('split_amount_agent')
                    ->numeric(),
                Forms\Components\TextInput::make('split_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_method_id')
                    ->numeric(),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('payment_date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscription.plan.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent.user.full_name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('NGN', locale: 'nl'),
                Tables\Columns\TextColumn::make('net_amount')->money('NGN', locale: 'nl'),
                Tables\Columns\TextColumn::make('split_amount_agent')->label('Agent Fee')->money('NGN', locale: 'nl'),
                Tables\Columns\TextColumn::make('split_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Payment Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'paid' => 'success',
                }),
                Tables\Columns\TextColumn::make('payment_method_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->dateTime( 'F j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                //
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
            'index' => Pages\ListSubsPayments::route('/'),
            'create' => Pages\CreateSubsPayment::route('/create'),
            'edit' => Pages\EditSubsPayment::route('/{record}/edit'),
        ];
    }
}
