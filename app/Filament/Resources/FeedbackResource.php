<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\School;
use Filament\Forms\Get;
use App\Models\Feedback;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\FeedbackResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FeedbackResource\RelationManagers;
use App\Filament\Resources\FeedbackResource\RelationManagers\ResponsesRelationManager;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';
    protected static ?string $navigationGroup = 'System Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Information
                Section::make('Feedback Campaign')
                    ->description('Configure your feedback campaign settings')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->required(),

                        DateTimePicker::make('start_date')
                            ->required(),

                        DateTimePicker::make('end_date')
                            ->after('start_date'),

                        TextInput::make('frequency_days')
                            ->numeric()
                            ->required()
                            ->helperText('How many days between showing feedback prompt'),

                        Toggle::make('is_active')
                            ->default(true),
                    ]),

                // School Targeting
                Section::make('Target Schools')
                    ->schema([
                        Select::make('target_schools')
                            ->multiple()
                            ->options(School::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Leave empty to target all schools'),
                    ]),

                // Questions Builder
                Section::make('Feedback Questions')
                    ->schema([
                        Repeater::make('questions')
                            ->schema([
                                TextInput::make('question')
                                    ->required(),

                                Select::make('type')
                                    ->options([
                                        'rating' => 'Star Rating',
                                        'text' => 'Text Response',
                                        'choice' => 'Multiple Choice',
                                    ])
                                    ->required(),

                                Repeater::make('options')
                                    ->schema([
                                        TextInput::make('option')
                                    ])
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('type') === 'choice'
                                    ),
                            ])
                            ->itemLabel(
                                fn(array $state): ?string =>
                                $state['question'] ?? null
                            )
                            ->collapsible()
                            ->defaultItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->sortable(),

                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Responses'),
            ])
            ->filters([
                TernaryFilter::make('is_active'),

                Filter::make('start_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedback::route('/'),
            'create' => Pages\CreateFeedback::route('/create'),
            'edit' => Pages\EditFeedback::route('/{record}/edit'),
        ];
    }
}
