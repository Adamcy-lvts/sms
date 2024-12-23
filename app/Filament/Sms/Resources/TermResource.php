<?php

namespace App\Filament\Sms\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Term;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\AcademicSession;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sms\Resources\TermResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\TermResource\RelationManagers;

class TermResource extends Resource
{
    protected static ?string $model = Term::class;
    protected static ?string $navigationGroup = 'School Management';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('academic_session_id')
                    ->label('Academic Session')
                    ->options(AcademicSession::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
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
                    ->label('Current Term'),
                // Add any other fields you need for your Term model
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_current')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Current')
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicSession.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Term Name')
                    ->searchable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTerms::route('/'),
            'create' => Pages\CreateTerm::route('/create'),
            'edit' => Pages\EditTerm::route('/{record}/edit'),
        ];
    }
}
