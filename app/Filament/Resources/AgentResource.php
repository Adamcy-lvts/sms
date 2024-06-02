<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Filament\Resources\AgentResource\RelationManagers;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('business_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('referral_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('subaccount_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()
                    ->default(20.00),
                Forms\Components\TextInput::make('fixed_rate')
                    ->numeric()
                    ->default(500.00),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referral_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subaccount_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fixed_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Joined On')
                    ->dateTime()
          
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
