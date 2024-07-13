<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Status;
use App\Models\Student;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\StudentResource\Pages;
use App\Filament\Sms\Resources\StudentResource\RelationManagers;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Student Information')
                    ->schema([
                        TextInput::make('school_id')->hidden(),
                        FileUpload::make('profile_picture')->label('Profile Picture')->required()->columnSpanFull(),
                        TextInput::make('first_name')->label('First Name')->required(),
                        TextInput::make('last_name')->label('Last Name')->required(),
                        TextInput::make('middle_name')->label('Middle Name'),
                        DatePicker::make('date_of_birth')->label('Date of Birth')->required(),
                        TextInput::make('phone_number')->label('Phone'),
                        Select::make('status_id')->label('Status')->options(Status::where('type','student')->pluck('name', 'id'))->default(1)->required(),
                        TextInput::make('identification_number')->label('Identification Number'),
                    ])
                    ->columns(2),

                Fieldset::make('Give Student Class Room')
                    ->schema([
                        Select::make('class_room_id')->label('Class Room')->options(ClassRoom::all()->pluck('name', 'id'))->required(),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_picture')->circular()->label('image')->label('Profile Picture')
                    ->height(50),
                Tables\Columns\TextColumn::make('admission_number')->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('admission.full_name')->label('Student Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('classRoom.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status.name')->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'graduated' => 'success',
                        'suspended' => 'danger',
                        'expelled' => 'danger',
                        'transferred' => 'warning',
                        'deceased' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'view' => Pages\StudentProfile::route('/{record}/profile'),
        ];
    }
}
