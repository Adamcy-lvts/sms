<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\TemplateVariable;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use App\Services\TemplateVariableCreator;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use App\Services\TemplateVariableGenerator;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\TemplateVariableResource\Pages;
use App\Filament\Sms\Resources\TemplateVariableResource\RelationManagers;

class TemplateVariableResource extends Resource
{
    protected static ?string $model = TemplateVariable::class;

    protected static ?string $navigationIcon = 'heroicon-o-variable';
    protected static ?string $navigationGroup = 'Document Management';
    // protected static ?string $navigationParentItem = 'Templates';
    protected static ?string $navigationLabel = 'Variables';

    // public static function form(Form $form): Form
    // {
    //     return $form->schema([
    //         Grid::make()->schema([
    //             Section::make('Variable Details')
    //                 ->description('Define template variable details')
    //                 ->schema([
    //                     // Data Source Selection
    //                     Select::make('source_model')
    //                         ->label('Select Data Source')
    //                         ->options(collect(TemplateVariableCreator::getAvailableModels())
    //                             ->pluck('label', 'model'))
    //                         ->reactive()

    //                         ->afterStateUpdated(fn($state, Set $set) =>
    //                         $set('selected_field', null)),

    //                     // Field Selection 
    //                     Select::make('selected_field')
    //                         ->label('Select Field')
    //                         ->options(function (Get $get) {
    //                             $modelClass = $get('source_model');
    //                             if (!$modelClass) return [];

    //                             $modelData = collect(TemplateVariableCreator::getAvailableModels())
    //                                 ->firstWhere('model', $modelClass);

    //                             return collect($modelData['fields'])
    //                                 ->pluck('label', 'name');
    //                         })
    //                         ->reactive()
    //                         ->required()
    //                         ->hidden(fn(Get $get) => !$get('source_model'))
    //                         ->afterStateUpdated(function ($state, Set $set, Get $get) {
    //                             if (!$state) return;

    //                             $modelData = collect(TemplateVariableCreator::getAvailableModels())
    //                                 ->firstWhere('model', $get('source_model'));

    //                             $field = $modelData['fields'][$state];

    //                             // Auto-set other fields based on selection
    //                             $set('display_name', $field['label']);
    //                             $set('name', Str::snake($state));
    //                             $set('field_type', TemplateVariableGenerator::mapDatabaseTypeToFieldType($field['type']));
    //                             $set('mapping', Str::snake(class_basename($get('source_model'))) . '.' . $state);
    //                         }),

    //                     // These can be hidden and auto-filled
    //                     TextInput::make('name')
    //                         ->disabled()
    //                         ->dehydrated(),

    //                     TextInput::make('mapping')
    //                         ->disabled()
    //                         ->dehydrated(),

    //                     TextInput::make('field_type')
    //                         ->disabled()
    //                         ->dehydrated(),

    //                     // These can be modified if needed
    //                     TextInput::make('display_name')
    //                         ->required()
    //                         ->label('Display Name'),

    //                     Select::make('category')
    //                         ->options(TemplateVariable::categories())
    //                         ->required()
    //                         ->default(fn(Get $get) =>
    //                         $get('source_model') ?
    //                             Str::snake(class_basename($get('source_model'))) :
    //                             null),

    //                     Textarea::make('description')
    //                         ->rows(2)
    //                         ->columnSpanFull(),

    //                     TextInput::make('sample_value')
    //                         ->placeholder('Example value for previews'),

    //                     Toggle::make('is_active')
    //                         ->label('Active')
    //                         ->default(true),
    //                 ])->columns(2),
    //         ])
    //     ]);
    // }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make()->schema([
                Section::make('Variable Details')
                    ->description('Define template variable details')
                    ->schema([
                        Select::make('source_model')
                            ->label('Select Data Source')
                            ->options(collect(TemplateVariableCreator::getAvailableModels())
                                ->pluck('label', 'model'))
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(fn($state, Set $set) =>
                            $set('selected_field', null)),

                        Select::make('selected_field')
                            ->label('Select Field')
                            ->options(function (Get $get) {
                                $modelClass = $get('source_model');
                                if (!$modelClass) return [];

                                $modelData = collect(TemplateVariableCreator::getAvailableModels())
                                    ->firstWhere('model', $modelClass);

                                if (!$modelData || !isset($modelData['fields'])) return [];

                                return collect($modelData['fields'])
                                    ->pluck('label', 'name');
                            })
                            ->reactive()
                            ->required()
                            ->hidden(fn(Get $get) => !$get('source_model'))
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (!$state) return;

                                $modelClass = $get('source_model');
                                $modelData = collect(TemplateVariableCreator::getAvailableModels())
                                    ->firstWhere('model', $modelClass);

                                if (!$modelData || !isset($modelData['fields'][$state])) return;

                                $field = $modelData['fields'][$state];

                                $set('display_name', $field['label']);
                                $set('name', $field['name']);
                                $set('field_type', TemplateVariableGenerator::mapDatabaseTypeToFieldType($field['type']));
                                $set('mapping', $field['mapping']);
                            }),

                        TextInput::make('name')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('mapping')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('field_type')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('display_name')
                            ->required()
                            ->label('Display Name'),

                        Select::make('category')
                            ->options(TemplateVariable::categories())
                            ->required()
                            ->default(fn(Get $get) =>
                            $get('source_model') ?
                                Str::snake(class_basename($get('source_model'))) :
                                null),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('sample_value')
                            ->placeholder('Example value for previews'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'primary' => 'all',
                        'success' => 'admission',
                        'warning' => 'academic',
                        'danger' => 'financial'
                    ]),

                TextColumn::make('field_type')
                    ->badge(),

                IconColumn::make('is_system')
                    ->boolean()
                    ->label('System')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('mapping')
                    ->toggleable()
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(TemplateVariable::categories()),

                TernaryFilter::make('is_system')
                    ->label('System Variables'),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(Model $record) => $record->is_system)
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplateVariables::route('/'),
            'create' => Pages\CreateTemplateVariable::route('/create'),
            'edit' => Pages\EditTemplateVariable::route('/{record}/edit'),
        ];
    }
}
