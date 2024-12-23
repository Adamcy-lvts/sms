<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BehavioralTrait;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\BehavioralTraitResource\Pages;
use App\Filament\Sms\Resources\BehavioralTraitResource\RelationManagers;

class BehavioralTraitResource extends Resource
{
    protected static ?string $model = BehavioralTrait::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Academic Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Behavioral Trait Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Select::make('category')
                        ->options([
                            'Learning Skills' => 'Learning Skills',
                            'Social Skills' => 'Social Skills',
                            'Personal Development' => 'Personal Development',
                            'Work Habits' => 'Work Habits'
                        ])
                        ->required()
                        ->searchable(),

                    Textarea::make('description')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    TextInput::make('display_order')
                        ->numeric()
                        ->default(0),

                    TextInput::make('weight')
                        ->numeric()
                        ->default(1.0)
                        ->step(0.1)
                        ->minValue(0)
                        ->maxValue(5)
                        ->helperText('Weight factor for this trait in overall assessment'),

                    Toggle::make('is_default')
                        ->label('Include in Report Cards')
                        ->default(true)
                        ->helperText('Whether this trait appears by default on report cards'),
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

                TextColumn::make('weight')
                    ->numeric(2)
                    ->sortable(),
            ])
            ->defaultSort('category', 'display_order')
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'Learning Skills' => 'Learning Skills',
                        'Social Skills' => 'Social Skills',
                        'Personal Development' => 'Personal Development',
                        'Work Habits' => 'Work Habits'
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
            'index' => Pages\ListBehavioralTraits::route('/'),
            'create' => Pages\CreateBehavioralTrait::route('/create'),
            'edit' => Pages\EditBehavioralTrait::route('/{record}/edit'),
        ];
    }
}
