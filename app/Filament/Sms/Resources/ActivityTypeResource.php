<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ActivityType;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\ActivityTypeResource\Pages;
use App\Filament\Sms\Resources\ActivityTypeResource\RelationManagers;

class ActivityTypeResource extends Resource
{
    protected static ?string $model = ActivityType::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Academic Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Activity Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Select::make('category')
                        ->options([
                            'Sports & Athletics' => 'Sports & Athletics',
                            'Arts & Culture' => 'Arts & Culture',
                            'Academic Clubs' => 'Academic Clubs',
                            'Leadership & Service' => 'Leadership & Service'
                        ])
                        ->required()
                        ->searchable(),

                    Textarea::make('description')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    TextInput::make('display_order')
                        ->numeric()
                        ->default(0),

                    ColorPicker::make('color')
                        ->nullable(),

                    TextInput::make('icon')
                        ->nullable()
                        ->prefix('fa-')
                        ->helperText('Font Awesome icon name (e.g., user, book)'),

                    Toggle::make('is_default')
                        ->label('Include in Report Cards')
                        ->default(true)
                        ->helperText('Whether this activity appears by default on report cards'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                IconColumn::make('is_default')
                    ->boolean()
                    ->label('In Reports'),

                TextColumn::make('display_order')
                    ->sortable(),

               

              
            ])
            ->defaultSort('category', 'display_order')
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'Sports & Athletics' => 'Sports & Athletics',
                        'Arts & Culture' => 'Arts & Culture',
                        'Academic Clubs' => 'Academic Clubs',
                        'Leadership & Service' => 'Leadership & Service'
                    ]),
                TernaryFilter::make('is_default')
                    ->label('In Reports'),
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
            'index' => Pages\ListActivityTypes::route('/'),
            'create' => Pages\CreateActivityType::route('/create'),
            'edit' => Pages\EditActivityType::route('/{record}/edit'),
        ];
    }
}
