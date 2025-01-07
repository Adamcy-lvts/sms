<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Status;
use App\Models\Student;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\Attendance;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Facades\Filament;
use App\Models\AttendanceRecord;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\AttendanceResource\Pages;
use Filament\Forms\Components\Actions\Action as FormAction;
use App\Filament\Sms\Resources\AttendanceResource\RelationManagers;

class AttendanceResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Attendance Record';
    protected static ?string $modelLabel = 'Attendance Record';
    protected static ?string $pluralModelLabel = 'Attendance Records';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Record Daily Attendance')
                ->description('Mark attendance for students')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('class_room_id')
                            ->label('Class')
                            ->options(fn() => Filament::getTenant()
                                ->classRooms()
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('attendance_records', []);
                            }),

                        Select::make('academic_session_id')
                            ->label('Academic Session')
                            ->options(fn() => Filament::getTenant()
                                ->academicSessions()
                                ->pluck('name', 'id'))
                            ->default(fn() => config('app.current_session')->id ?? null)
                            ->required()
                            ->disabled(),

                        Select::make('term_id')
                            ->label('Term')
                            ->options(fn() => Filament::getTenant()
                                ->terms()
                                ->pluck('name', 'id'))
                            ->default(fn() => config('app.current_term')->id ?? null)
                            ->required()
                            ->disabled(),

                        DatePicker::make('date')
                            ->label('Attendance Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->weekStartsOnMonday()
                            ->closeOnDateSelection()
                            ->afterStateUpdated(function ($state, callable $get, Set $set) {
                                $existingAttendance = AttendanceRecord::where([
                                    'class_room_id' => $get('class_room_id'),
                                    'date' => $state,
                                ])->exists();

                                if ($existingAttendance) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Attendance Already Recorded')
                                        ->body('Attendance records already exist for this date.')
                                        ->persistent()
                                        ->send();
                                }
                            }),
                    ]),

                    // Section::make('Quick Actions')
                    //     ->schema([
                    //         Actions::make([
                    //             FormAction::make('load_students')
                    //                 ->label('Load Class Students')
                    //                 ->icon('heroicon-o-users')
                    //                 ->action(function (Set $set, $get) {
                    //                     $classId = $get('class_room_id');
                    //                     if (!$classId) {
                    //                         Notification::make()
                    //                             ->warning()
                    //                             ->title('Select Class')
                    //                             ->body('Please select a class first.')
                    //                             ->send();
                    //                         return;
                    //                     }

                    //                     $students = Student::where('class_room_id', $classId)
                    //                         ->where('status_id', Status::where('name', 'active')->where('type','student')->first()->id)
                    //                         ->get()
                    //                         ->map(fn($student) => [
                    //                             'student_id' => $student->id,
                    //                             'status' => 'present'
                    //                         ])
                    //                         ->toArray();

                    //                     $set('attendance_records', $students);
                    //                 }),

                    //             FormAction::make('mark_all_present')
                    //                 ->label('Mark All Present')
                    //                 ->icon('heroicon-o-check-circle')
                    //                 ->color('success')
                    //                 ->action(function (Set $set, $get) {
                    //                     $records = $get('attendance_records');
                    //                     foreach ($records as $key => $record) {
                    //                         $records[$key]['status'] = 'present';
                    //                     }
                    //                     $set('attendance_records', $records);
                    //                 }),

                    //             FormAction::make('clear_all')
                    //                 ->label('Clear All')
                    //                 ->icon('heroicon-o-x-circle')
                    //                 ->color('danger')
                    //                 ->requiresConfirmation()
                    //                 ->action(function (Set $set) {
                    //                     $set('attendance_records', []);
                    //                 }),
                    //         ])
                    //     ])
                    //     ->collapsed(),

                    Section::make('Students')
                        ->description('Mark attendance status for each student')
                        ->schema([
                            Repeater::make('attendance_records')
                                ->schema([
                                    Select::make('student_id')
                                        ->label('Student')
                                        ->options(function () {
                                            return Student::query()
                                                ->where('school_id', Filament::getTenant()->id)
                                                ->with(['classRoom'])
                                                ->get()
                                                ->mapWithKeys(fn($student) => [
                                                    $student->id => "{$student->full_name} - {$student->classRoom->name}"
                                                ]);
                                        })
                                        ->searchable()
                                        ->required()
                                        ->live(),

                                    Select::make('status')
                                        ->options([
                                            'present' => 'Present',
                                            'absent' => 'Absent',
                                            'late' => 'Late',
                                            'excused' => 'Excused'
                                        ])
                                        ->required()
                                        ->default('present')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if ($state !== 'late') {
                                                $set('arrival_time', null);
                                            }
                                            if (!in_array($state, ['absent', 'excused', 'late'])) {
                                                $set('remarks', null);
                                            }
                                        }),

                                    TimePicker::make('arrival_time')
                                        ->visible(fn(Get $get) => $get('status') === 'late')
                                        ->label('Arrival Time')
                                        ->seconds(false)
                                        ->native(false)
                                        ->required(fn(Get $get) => $get('status') === 'late'),

                                    TextInput::make('remarks')
                                        ->visible(fn(Get $get) => in_array($get('status'), ['absent', 'excused', 'late']))
                                        ->placeholder('Reason for absence/late arrival')
                                        ->required(fn(Get $get) => in_array($get('status'), ['absent', 'excused']))
                                        ->maxLength(255),
                                ])
                                ->columns(4)
                                ->defaultItems(0)
                                ->addActionLabel('Add Student')
                                ->collapsible()
                                ->reorderableWithButtons()
                                ->cloneable()
                                ->itemLabel(
                                    fn(array $state): ?string =>
                                    isset($state['student_id'])
                                        ? Student::find($state['student_id'])?->full_name . ' - ' . ($state['status'] ?? 'N/A')
                                        : null
                                )
                        ])
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('admission_number', 'like', "%{$search}%");
                        });
                    })
                    ->description(fn($record) => $record->student?->admission_number),

                TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'excused' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('arrival_time')
                    ->label('Arrival Time')
                    ->time()
                    ->visible(fn($record) => $record && $record->status === 'late'),

                TextColumn::make('remarks')
                    ->wrap()
                    ->words(10)
                    ->visible(fn($record) => $record && in_array($record->status, ['absent', 'excused', 'late'])),

                TextColumn::make('modifiedBy.ShortName')
                    ->label('Last Modified')
                    // ->dateTime('j M Y, g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn($record) => $record->modifiedBy?->name ?? ''),

                TextColumn::make('recordedBy.ShortName')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true)
                // ->description(fn($record) => $record->created_at->format('j M Y, g:i A')),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('class_room_id')
                    ->label('Class')
                    ->options(fn() => Filament::getTenant()
                        ->classRooms()
                        ->pluck('name', 'id'))
                    ->multiple(),

                SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'excused' => 'Excused'
                    ])
                    ->multiple(),

                SelectFilter::make('academic_session_id')
                    ->label('Academic Session')
                    ->options(fn() => Filament::getTenant()
                        ->academicSessions()
                        ->pluck('name', 'id'))
                    ->default(fn() => config('app.current_session')->id ?? null),

                SelectFilter::make('term_id')
                    ->label('Term')
                    ->options(fn() => Filament::getTenant()
                        ->terms()
                        ->pluck('name', 'id'))
                    ->default(fn() => config('app.current_term')->id ?? null),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->native(false)
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')->native(false)
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Action::make('takeAttendance')
                    ->label('Take Attendance')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('primary')
                    ->url(fn(): string => static::getUrl('attendance')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No attendance records')
            ->emptyStateDescription('Start by recording attendance for a class.')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'attendance' => Pages\AttendancePage::route('/take-attendance'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
