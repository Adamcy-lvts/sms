<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\SystemAnnouncement;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SystemAnnouncementResource\Pages;
use App\Filament\Resources\SystemAnnouncementResource\RelationManagers;

class SystemAnnouncementResource extends Resource
{
    protected static ?string $model = SystemAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                RichEditor::make('message')
                    ->required(),

                Select::make('type')
                    ->options([
                        'info' => 'Information',
                        'warning' => 'Warning',
                        'danger' => 'Danger'
                    ])
                    ->default('info')
                    ->required(),

                ColorPicker::make('background_color')
                    ->nullable(),

                ColorPicker::make('text_color')
                    ->nullable(),

                Toggle::make('is_active')
                    ->default(true),

                Toggle::make('is_dismissible')
                    ->default(true),

                DateTimePicker::make('starts_at')
                    ->nullable(),

                DateTimePicker::make('ends_at')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'info' => 'info',
                        'warning' => 'warning',
                        'danger' => 'danger',
                    ]),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->dateTime(),
                TextColumn::make('ends_at')
                    ->dateTime(),
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
            'index' => Pages\ListSystemAnnouncements::route('/'),
            'create' => Pages\CreateSystemAnnouncement::route('/create'),
            'edit' => Pages\EditSystemAnnouncement::route('/{record}/edit'),
        ];
    }
}
