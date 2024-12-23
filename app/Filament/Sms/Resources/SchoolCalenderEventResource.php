<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\SchoolCalendarEvent;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\SchoolCalenderEventResource\Pages;
use App\Filament\Sms\Resources\SchoolCalenderEventResource\RelationManagers;

class SchoolCalenderEventResource extends Resource
{
    protected static ?string $model = SchoolCalendarEvent::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'School Management';
    protected static ?string $navigationLabel = 'Calendar Events';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('type')
                    ->options([
                        'holiday' => 'Holiday',
                        'event' => 'School Event',
                        'break' => 'Break Period'
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->format('Y-m-d')
                    ->native(false),

                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->format('Y-m-d')
                    ->native(false)
                    ->afterOrEqual('start_date'),

                Forms\Components\Toggle::make('excludes_attendance')
                    ->label('Exclude from Attendance')
                    ->helperText('If enabled, these dates will not count as school days')
                    ->default(false),

                Forms\Components\Toggle::make('is_recurring')
                    ->label('Recurring Event')
                    ->reactive(),

                Forms\Components\Select::make('recurrence_pattern')
                    ->options([
                        'yearly' => 'Yearly',
                        'termly' => 'Every Term',
                        'monthly' => 'Monthly'
                    ])
                    ->visible(fn(Forms\Get $get): bool => $get('is_recurring')),

                Forms\Components\ColorPicker::make('color')
                    ->label('Event Color'),
            ]),

            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'holiday' => 'danger',
                        'event' => 'success',
                        'break' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->boolean(),

                Tables\Columns\IconColumn::make('excludes_attendance')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'holiday' => 'Holiday',
                        'event' => 'School Event',
                        'break' => 'Break Period'
                    ]),

                Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($query) => $query->whereDate('start_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($query) => $query->whereDate('start_date', '<=', $data['until'])
                            );
                    })
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
            'index' => Pages\ListSchoolCalenderEvents::route('/'),
            'create' => Pages\CreateSchoolCalenderEvent::route('/create'),
            'edit' => Pages\EditSchoolCalenderEvent::route('/{record}/edit'),
        ];
    }
}
