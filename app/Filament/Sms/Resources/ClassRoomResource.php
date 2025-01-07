<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\ClassRoomResource\Pages;
use App\Filament\Sms\Resources\ClassRoomResource\RelationManagers;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\ClassRoomStatsOverview;
use App\Filament\Sms\Resources\ClassRoomResource\RelationManagers\StudentsRelationManager;

class ClassRoomResource extends Resource
{
    protected static ?string $model = ClassRoom::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'School Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Class Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('capacity')
                                    ->label('Capacity')
                                    ->integer()
                                    ->required()
                                    ->minValue(1),
                                Forms\Components\Select::make('subjects')
                                    ->multiple()
                                    ->relationship('subjects', 'name')
                                    ->preload()
                                    ->searchable(),
                            ]),
                        Forms\Components\RichEditor::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->sortable(),
                
                // TextColumn::make('students_count')
                //     ->counts('students')
                //     ->label('Students'),
                // TextColumn::make('subjects_count')
                //     ->counts('subjects')
                //     ->label('Subjects'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school')
                    ->relationship('school', 'name'),
                Tables\Filters\Filter::make('empty')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('students'))
                    ->toggle(),
            ])
            ->defaultSort('name', 'asc')
            ->actions([
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

    public static function getWidgets(): array
    {
        return [
            ClassRoomStatsOverview::class,

        ];
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassRooms::route('/'),
            'create' => Pages\CreateClassRoom::route('/create'),
            'edit' => Pages\EditClassRoom::route('/{record}/edit'),
            'view' => Pages\ViewClassRoom::route('/{record}')
        ];
    }
}
