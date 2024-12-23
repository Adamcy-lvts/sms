<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\SubjectAssessment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\SubjectAssessmentResource\Pages;
use App\Filament\Sms\Resources\SubjectAssessmentResource\RelationManagers;

class SubjectAssessmentResource extends Resource
{
    protected static ?string $model = SubjectAssessment::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assessment Details')
                ->description('Create or edit a subject assessment')
                ->schema([
                    Select::make('subject_id')
                        ->relationship('subject', 'name')
                        ->preload()
                        ->required()
                        ->searchable(),

                    Select::make('class_room_id')
                        ->relationship('classRoom', 'name')
                        ->preload()
                        ->required()
                        ->searchable(),

                    Select::make('teacher_id')
                        ->label('Teacher')
                        ->relationship(
                            name: 'teacher',
                            titleAttribute: 'id',
                            modifyQueryUsing: fn(Builder $query) => $query->with('staff')->whereHas('staff'),
                        )
                        ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->staff?->full_name ?? 'Unknown')
                        ->preload()
                        ->required()
                        ->searchable(['staff.first_name', 'staff.last_name']),


                    Select::make('academic_session_id')
                        ->relationship('academicSession', 'name')
                        ->default(fn() => config('app.current_session')->id)
                        ->required(),

                    Select::make('term_id')
                        ->relationship('term', 'name')
                        ->default(fn() => config('app.current_term')->id)
                        ->required(),

                    Select::make('assessment_type_id')
                        ->relationship('assessmentType', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    DatePicker::make('assessment_date')
                        ->required()
                        ->native(false),

                    Textarea::make('description')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn() => auth()->id())
                        ->dehydrated(true),

                    Toggle::make('is_published')
                        ->label('Publish Assessment')
                        ->default(false)
                        ->helperText('Published assessments are visible to students and parents'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('classRoom.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable(),

                Tables\Columns\TextColumn::make('term.name')->sortable(),

                Tables\Columns\TextColumn::make('teacher.staff.full_name')
                    ->label('Teacher')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('assessmentType.name')
                    ->label('Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('assessment_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->sortable()
                    ->label('Published'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Subject'),

                Tables\Filters\SelectFilter::make('class_room_id')
                    ->relationship('classRoom', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Class'),

                Tables\Filters\SelectFilter::make('assessment_type_id')
                    ->relationship('assessmentType', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Assessment Type'),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published Status')
                    ->placeholder('All')
                    ->trueLabel('Published')
                    ->falseLabel('Draft'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('enterGrades')
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn(SubjectAssessment $record) =>
                        StudentGradeResource::getUrl('create', ['assessment' => $record->id]))
                        ->visible(fn(SubjectAssessment $record) => !$record->is_published),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publishSelected')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(fn(Collection $records) => $records->each->update(['is_published' => true])),
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
            'index' => Pages\ListSubjectAssessments::route('/'),
            'create' => Pages\CreateSubjectAssessment::route('/create'),
            'edit' => Pages\EditSubjectAssessment::route('/{record}/edit'),
        ];
    }
}
