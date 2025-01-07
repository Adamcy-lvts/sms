<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Template;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\TemplateVariable;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use FilamentTiptapEditor\Enums\TiptapOutput;
use Illuminate\Database\Eloquent\Collection;
use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\TemplateResource\Pages;
use App\Filament\Sms\Resources\TemplateResource\RelationManagers;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Document Management';

    // TemplateResource.php
    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make([
                'default' => 1,
                'lg' => 2,
            ])->schema([
                Section::make('Template Details')

                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'lg' => 2,
                        ])->schema([
                            TextInput::make('name')
                                ->required(),

                            TextInput::make('category')
                                // ->options()
                                ->required()
                                ->reactive(),

                            Textarea::make('description')->columnSpanFull(),

                            Grid::make([
                                'default' => 1,
                                'lg' => 2,
                            ])->schema([
                                Toggle::make('is_active')
                                    ->default(true),

                                Toggle::make('is_default'),
                            ]),

                        ]),

                    ]),

                Section::make('Template Editor')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        // In TemplateResource.php
                        TiptapEditor::make('content')
                            ->profile('default')
                            // ->tools(['bold', 'italic', 'image']) // Make sure 'image' is included
                            ->mergeTags(function () {
                                return TemplateVariable::where('school_id', Filament::getTenant()->id)
                                    ->where('is_active', true)
                                    ->pluck('name')
                                    ->toArray();
                            })
                            ->output(TiptapOutput::Json)
                            ->maxContentWidth('5xl')
                        // TiptapEditor::make('content')
                        //     ->profile('default')
                        //     ->tools(['bold', 'italic', 'heading', 'image']) // Make sure image is included
                        //     ->mergeTags(function () {
                        //         return TemplateVariable::where('school_id', Filament::getTenant()->id)
                        //             ->where('is_active', true)
                        //             ->pluck('name')
                        //             ->toArray();
                        //     })
                        //     ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                    ]),

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

                TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'primary' => 'admission',
                        'success' => 'academic',
                        'warning' => 'financial',
                        'danger' => 'general',
                    ]),

                TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('is_active')
                    ->badge()
                    ->label('Status')
                    ->colors([
                        'success' => fn($state): bool => $state === true,
                        'danger' => fn($state): bool => $state === false,
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => fn($state): bool => $state === true,
                        'heroicon-o-x-circle' => fn($state): bool => $state === false,
                    ]),

                TextColumn::make('version')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(Template::categories()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                // ->url(fn(Template $record): string =>
                // route('template.preview', $record))
                // ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->action(fn(Collection $records) =>
                        $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->action(fn(Collection $records) =>
                        $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-mark'),
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
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
