<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
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

class ClassRoomResource extends Resource implements HasShieldPermissions
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

                                Forms\Components\Select::make('level')
                                    ->options([
                                        'nursery' => 'Nursery',
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                    ])
                                    ->required()
                                    ->native(false),

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

                TextColumn::make('level')
                    ->badge()
                    ->colors([
                        'warning' => 'nursery',
                        'primary' => 'primary',
                        'success' => 'secondary',
                    ]),

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
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'nursery' => 'Nursery',
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                    ]),
            ])
            ->defaultSort('name', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('takeAttendance')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->url(fn (ClassRoom $record): string => 
                        route('filament.sms.resources.class-rooms.attendance', ['record' => $record]))
                    ->visible(fn(): bool => auth()->user()->can('can_take_attendance_class_room'))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // If user is a teacher, only show their assigned classes
        if (auth()->user()->hasRole('teacher')) {
            $teacher = \App\Models\Teacher::where('staff_id', auth()->user()->staff->id)->first();
            
            if ($teacher) {
                $query->whereHas('teachers', function ($q) use ($teacher) {
                    $q->where('teachers.id', $teacher->id);
                });
            }
        }

        return $query;
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

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'can_take_attendance', // Add the custom permission
        ];
    }
}
