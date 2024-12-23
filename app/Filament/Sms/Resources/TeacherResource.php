<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use Faker\Provider\ar_EG\Text;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\TeacherResource\Pages;
use App\Filament\Sms\Resources\TeacherResource\RelationManagers;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        // 'staff_id',
        // 'school_id',
        // 'subject_ids',
        // 'class_room_ids',
        // 'specialization',
        // 'teaching_experience',

        return $form
            ->schema([
                Section::make('Search Staff')
                    ->description('Search for staff to assign as teacher')
                    ->schema([
                        Select::make('staff_id')->label('Staff')->live()
                            ->searchable()->getOptionLabelUsing(function ($value) {
                                $staff = Staff::find($value);
                                if (!$staff) return null;

                                $label = $staff->full_name . ' - ' . $staff->staff_id_number;
                                if ($staff->full_name) {
                                    $label .= ' ( ' . $staff->full_name . ')';
                                }
                                return $label;
                            }),
                    ]),

                Select::make('subject_ids')->label('Subjects')->options(Subject::all()->pluck('name', 'id'))->multiple()->searchable()->native(false),
                Select::make('class_room_ids')->label('Class Rooms')->options(ClassRoom::all()->pluck('name', 'id'))->searchable()->multiple()->native(false),
                Forms\Components\TextInput::make('specialization')->label('Specialization'),
                Forms\Components\TextInput::make('teaching_experience')->label('Teaching Experience'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('staff.profile_picture_url')->label('Profile Picture')->circular(),
                Tables\Columns\TextColumn::make('staff.full_name')->label('Staff Name'),
                Tables\Columns\TextColumn::make('staff.phone_number')->label('Phone Number'),
                Tables\Columns\TextColumn::make('classRoom.name')->label('Class Taken')->default('No class assign'),
                Tables\Columns\TextColumn::make('Subjects.name')->label('Subjects')->default('No subject assign'),
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
            'view' => Pages\ViewTeacher::route('/{record}/teacher-profile'),
        ];
    }
}
