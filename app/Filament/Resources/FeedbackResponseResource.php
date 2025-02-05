<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbackResponseResource\Pages;
use App\Filament\Resources\FeedbackResponseResource\RelationManagers;
use App\Models\FeedbackResponse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FeedbackResponseResource\RelationManagers\FeedbackTrackingRelationManager;

class FeedbackResponseResource extends Resource
{
    protected static ?string $model = FeedbackResponse::class;


    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('feedback_id')
                            ->relationship('feedback', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('school_id')
                            ->relationship('school', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\KeyValue::make('responses')
                            ->label('Response Details')
                            ->keyLabel('Question')
                            ->valueLabel('Answer')
                            ->reorderable(),

                        Forms\Components\Textarea::make('comments')
                            ->label('Additional Comments')
                            ->rows(3),

                        // Forms\Components\Rating::make('rating')
                        //     ->label('Overall Rating')
                        //     ->min(1)
                        //     ->max(5),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('feedback.title')
                    ->label('Campaign')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Respondent')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? "★ {$state}/5" : '-'),

                Tables\Columns\TextColumn::make('comments')
                    ->label('Comments'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Rating Filter
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        '1' => '1 Star',
                        '2' => '2 Stars',
                        '3' => '3 Stars',
                        '4' => '4 Stars',
                        '5' => '5 Stars',
                    ]),

                // Date Range Filter
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    public static function getRelations(): array
    {
        return [
            RelationManagers\FeedbackRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedbackResponses::route('/'),
            'create' => Pages\CreateFeedbackResponse::route('/create'),
            'edit' => Pages\EditFeedbackResponse::route('/{record}/edit'),
        ];
    }
}
