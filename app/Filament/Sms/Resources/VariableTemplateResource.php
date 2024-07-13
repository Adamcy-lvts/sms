<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Admission;
use Filament\Tables\Table;
use App\Models\VariableTemplate;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\VariableTemplateResource\Pages;
use App\Filament\Sms\Resources\VariableTemplateResource\RelationManagers;

class VariableTemplateResource extends Resource
{
    protected static ?string $model = VariableTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Template Variables';

    protected static ?string $navigationParentItem = 'Templates';

    public static function form(Form $form): Form
    {
         // Fetch the columns from the Admission model
         $admissionColumns = Schema::getColumnListing((new Admission)->getTable());
         
        return $form
            ->schema([
                Forms\Components\TextInput::make('variable_name')
                    ->maxLength(255),
                Forms\Components\Select::make('mapping')
                    ->label('Mapping')
                    ->options(array_combine($admissionColumns, $admissionColumns))
                    ->required(),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('variable_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mapping'),
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
            'index' => Pages\ListVariableTemplates::route('/'),
            'create' => Pages\CreateVariableTemplate::route('/create'),
            'edit' => Pages\EditVariableTemplate::route('/{record}/edit'),
        ];
    }
}
