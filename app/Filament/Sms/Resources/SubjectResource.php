<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Subject;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\SubjectResource\Pages;
use App\Filament\Sms\Resources\SubjectResource\RelationManagers;
use League\CommonMark\Normalizer\SlugNormalizer;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')->required(),

                Forms\Components\TextInput::make('slug'),
    
                Forms\Components\TextInput::make('description')
                    ->label('Description'),

                Forms\Components\TextInput::make('position')
                    ->label('Position')
                    ->integer(),

                Forms\Components\TextInput::make('color')
                    ->label('Color'),

                Forms\Components\Checkbox::make('is_optional')
                    ->label('Is Optional'),

                Forms\Components\Checkbox::make('is_active')
                    ->label('Is Active'),

               
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('description'),
                TextColumn::make('position'),
                TextColumn::make('color'),
                TextColumn::make('is_optional'),
                TextColumn::make('is_active'),
                TextColumn::make('is_archived'),
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
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
