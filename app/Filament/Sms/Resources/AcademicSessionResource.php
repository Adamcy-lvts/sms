<?php

namespace App\Filament\Sms\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\AcademicSession;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\AcademicSessionResource\Pages;
use App\Filament\Sms\Resources\AcademicSessionResource\RelationManagers;

class AcademicSessionResource extends Resource
{
    protected static ?string $model = AcademicSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'School Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\Select::make('school_id')->relationship(name: 'school', titleAttribute: 'name'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\DatePicker::make('start_date')
                    ->native(false)
                    ->required()
                    ->maxDate(Carbon::now()->addYears(2)->endOfYear())
                    ->beforeOrEqual('end_date'),
                Forms\Components\DatePicker::make('end_date')
                    ->native(false)
                    ->required()
                    ->maxDate(Carbon::now()->addYears(2)->endOfYear())
                    ->afterOrEqual('start_date'),
                Forms\Components\Toggle::make('is_current')
                    ->label('Current Session'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                // Tables\Columns\IconColumn::make('is_current')
                //     ->boolean()
                //     ->trueIcon('heroicon-o-check-circle')
                //     ->falseIcon('heroicon-o-x-circle')
                //     ->label('Current')
                //     ->sortable(),
                // Add a toggle button to make term current or not
                Tables\Columns\ToggleColumn::make('is_current')
                    ->label('Current'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'terms' => RelationManagers\TermsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicSessions::route('/'),
            'create' => Pages\CreateAcademicSession::route('/create'),
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
