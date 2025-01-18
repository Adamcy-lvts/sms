<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\LegalDocument;
use Filament\Resources\Resource;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use FilamentTiptapEditor\Enums\TiptapOutput;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LegalDocumentResource\Pages;
use App\Filament\Resources\LegalDocumentResource\RelationManagers;

class LegalDocumentResource extends Resource
{
    protected static ?string $model = LegalDocument::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Settings';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set) =>
                            $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'terms' => 'Terms of Service',
                                'privacy' => 'Privacy Policy',
                            ]),

                        Forms\Components\TextInput::make('version')
                            ->helperText('e.g., 1.0.0'),

                        TiptapEditor::make('content')
                            ->profile('default')
                            ->output(TiptapOutput::Html)
                            ->maxContentWidth('5xl'),

                        // Forms\Components\RichEditor::make('content')
                        //     ->required()
                        //     ->toolbarButtons([
                        //         'bold',
                        //         'italic',
                        //         'link',
                        //         'bulletList',
                        //         'orderedList',
                        //         'h2',
                        //         'h3',
                        //     ])
                        //     ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->native(false)
                            ->label('Publish Date'),
                    ])
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('version'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'terms' => 'Terms of Service',
                        'privacy' => 'Privacy Policy',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
            'index' => Pages\ListLegalDocuments::route('/'),
            'create' => Pages\CreateLegalDocument::route('/create'),
            'edit' => Pages\EditLegalDocument::route('/{record}/edit'),
        ];
    }
}
