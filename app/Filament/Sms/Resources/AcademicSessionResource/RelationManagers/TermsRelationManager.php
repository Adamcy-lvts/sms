<?php

namespace App\Filament\Sms\Resources\AcademicSessionResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';
    protected static ?string $title = 'Academic Terms';
    protected static int $itemsPerPage = 10;

    // Add a property to store the school ID
    protected $schoolId;


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Name field
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                // Date Section
                Forms\Components\Section::make('Term Period')
                    ->description('Set the start and end dates for this term')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->required()
                                    ->native(false),

                                Forms\Components\DatePicker::make('end_date')
                                    ->required()
                                    ->native(false)
                                    ->after('start_date'),
                            ]),
                    ]),

                Forms\Components\Toggle::make('is_current')
                    ->label('Set as Current Term')
                    ->helperText('Only one term can be current at a time')
                    ->default(false)
                    ->live(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_current')
                    ->boolean()
                    ->sortable()
                    ->label('Current'),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): Model {
                        // Get the current tenant (school)
                        $school = Filament::getTenant();
                        
                        // If this is going to be the current term, unset others
                        if ($data['is_current']) {
                            $model::query()
                                ->where('school_id', $school->id)
                                ->where('is_current', true)
                                ->update(['is_current' => false]);
                        }
                        
                        // Create the new term with school_id and academic_session_id
                        return $model::create([
                            ...$data,
                            'school_id' => $school->id,
                            'academic_session_id' => $this->ownerRecord->id,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        // Get the current tenant (school)
                        $school = Filament::getTenant();
                        
                        // If this is going to be the current term, unset others
                        if ($data['is_current'] && !$record->is_current) {
                            $record::query()
                                ->where('school_id', $school->id)
                                ->where('is_current', true)
                                ->update(['is_current' => false]);
                        }
                        
                        $record->update($data);
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

}
