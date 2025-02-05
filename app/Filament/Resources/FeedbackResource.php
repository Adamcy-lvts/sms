<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeebackResource\RelationManagers\TrackingRelationManager;
use Filament\Forms;
use Filament\Tables;
use App\Models\School;
use Filament\Forms\Get;
use App\Models\Feedback;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Group;
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
                // Main Grid Layout with 2/3 : 1/3 split
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])->schema([
                    // Left Column (2/3 width) - Main Content
                    Group::make([
                        // Campaign Details Section
                        Section::make('Feedback Campaign')
                            ->description('Configure your feedback campaign settings')
                            ->icon('heroicon-o-megaphone')
                            ->schema([
                                // Campaign basics in a 2-column grid
                                Grid::make(2)->schema([
                                    TextInput::make('title')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan('full'),

                                    Textarea::make('description')
                                        ->required()
                                        ->columnSpan('full'),

                                    // Group date pickers together
                                    DateTimePicker::make('start_date')
                                        ->required(),

                                    DateTimePicker::make('end_date')
                                        ->after('start_date'),
                                ]),

                                // Frequency and status in another grid
                                Grid::make(2)->schema([
                                    TextInput::make('frequency_days')
                                        ->numeric()
                                        ->required()
                                        ->helperText('How many days between showing feedback prompt'),

                                    Toggle::make('is_active')
                                        ->default(true)
                                        ->inline(false),
                                ]),
                            ]),

                        // Questions Section
                        Section::make('Feedback Questions')
                            ->description('Design your questionnaire')
                            ->icon('heroicon-o-question-mark-circle')
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
                                            ->visible(fn(Get $get) => $get('type') === 'choice'),
                                    ])
                                    ->itemLabel(fn(array $state): ?string => $state['question'] ?? null)
                                    ->collapsible()
                                    ->defaultItems(1),
                            ]),
                    ])->columnSpan(2),

                    // Right Column (1/3 width) - Target Schools
                    Group::make([
                        Section::make('Target Schools')
                            ->description('Select schools to receive feedback')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Select::make('target_schools')
                                    ->multiple()
                                    ->options(School::pluck('name', 'id'))
                                    ->searchable()
                                    ->helperText('Leave empty to target all schools'),
                            ]),
                    ])->columnSpan(1),
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
            TrackingRelationManager::class,
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
