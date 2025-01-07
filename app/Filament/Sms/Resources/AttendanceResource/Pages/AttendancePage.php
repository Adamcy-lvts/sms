<?php

namespace App\Filament\Sms\Resources\AttendanceResource\Pages;


use App\Models\Status;
use App\Models\Student;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
// use Filament\Tables;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Sms\Resources\AttendanceResource;


class AttendancePage extends Page implements HasForms, HasTable
{

    protected static string $resource = AttendanceResource::class;
    protected static string $view = 'filament.sms.resources.attendance-resource.pages.attendance-page';
    protected static ?string $title = 'Take Attendance';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 6;

    use InteractsWithTable;
    use InteractsWithForms;

    public function mount(): void
    {
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');

        $this->form->fill([
            'academic_session_id' => $currentSession->id ?? null,
            'term_id' => $currentTerm->id ?? null,
            'date' => now()->format('Y-m-d'),
        ]);
    }

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            Log::error('No tenant found in attendance page');
            return $table->query(Student::query()->where('id', 0)); // Empty query
        }

        return $table
            ->query(Student::query()->with(['admission']))
            ->defaultSort('full_name', 'asc')
            ->columns([
                ImageColumn::make('profile_picture')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn() => asset('img/default.jpg')),

                TextColumn::make('full_name')
                    ->label('Student Name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(),

                TextColumn::make('admission.admission_number')
                    ->label('Admission No.')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md')
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A'),

                TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ?? 'Unassigned'),

                TextColumn::make('todayAttendance')
                    ->label('Today\'s Attendance')
                    ->state(function (Student $record): string {
                        $attendance = AttendanceRecord::where([
                            'student_id' => $record->id ?? 0,
                            'date' => now()->toDateString(),
                        ])->first();

                        if (!$attendance) return '-';

                        return ($attendance->status ?? '-') . 
                            ($attendance->modified_by ? ' (edited)' : '');
                    })
                    ->badge()
                    ->color(fn(string $state): string => match (explode(' ', $state)[0]) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'error' => 'gray',
                        default => 'gray',
                    })
                    ->tooltip(function (Student $record): ?string {
                        $attendance = AttendanceRecord::where([
                            'student_id' => $record->id ?? 0,
                            'date' => now()->toDateString(),
                        ])->first();

                        if (!$attendance || !$attendance->modified_by) {
                            return null;
                        }

                        $modifiedBy = $attendance->modifiedBy?->name ?? 'Unknown User';
                        $remarks = $attendance->remarks ?? 'No reason provided';

                        return "Modified by: {$modifiedBy}\nReason: {$remarks}";
                    }),
            ])
            ->filters([
                SelectFilter::make('class_room_id')
                    ->label('Class')
                    ->options(function () use ($tenant) {
                        return ClassRoom::where('school_id', $tenant?->id ?? 0)
                            ->pluck('name', 'id')
                            ->filter()
                            ->toArray() ?? [];
                    })
                    ->multiple()
                    ->preload(),


                Filter::make('attendance_date')
                    ->form([
                        DatePicker::make('attendance_date')
                            ->default(now()),
                    ])

            ], layout: FiltersLayout::AboveContent)
            ->actions([

                Action::make('editAttendance')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Edit Attendance')
                    ->visible(fn(Student $record) => \App\Models\AttendanceRecord::where([
                        'student_id' => $record->id,
                        'date' => now()->toDateString(),
                    ])->exists())
                    ->form([
                        Select::make('status')
                            ->label('Update Status')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                            ])
                            ->required(),
                        Textarea::make('remarks')
                            ->label('Reason for Change')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data) {
                        try {
                            $attendance = AttendanceRecord::where([
                                'student_id' => $record->id ?? 0,
                                'date' => now()->toDateString(),
                            ])->first();

                            if (!$attendance) {
                                throw new \Exception('Attendance record not found');
                            }

                            $oldStatus = $attendance->status ?? 'unknown';

                            $attendance->update([
                                'status' => $data['status'] ?? 'present',
                                'remarks' => $data['remarks'] ?? '',
                                'modified_by' => Auth::id(),
                            ]);

                            // Recalculate attendance summary
                            \App\Models\AttendanceSummary::calculateForStudent(
                                $record,
                                config('app.current_session')->id,
                                config('app.current_term')->id
                            );

                            Notification::make()
                                ->success()
                                ->title('Attendance Updated')
                                ->body("Status changed from {$oldStatus} to {$data['status']}")
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Error updating attendance: ' . $e->getMessage());
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to update attendance')
                                ->send();
                        }
                    }),

                Action::make('markPresent')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Present')
                    ->modalDescription(fn(Student $record) => "Mark {$record->full_name} as present?")
                    ->hidden(function (Student $record): bool {
                        return AttendanceRecord::where([
                            'student_id' => $record->id,
                            'date' => now()->toDateString(),
                            'academic_session_id' => config('app.current_session')->id,
                            'term_id' => config('app.current_term')->id,
                        ])->exists();
                    })
                    ->action(function (Student $record): void {
                        AttendanceRecord::create([
                            'school_id' => Filament::getTenant()->id,
                            'class_room_id' => $record->class_room_id,
                            'student_id' => $record->id,
                            'academic_session_id' => config('app.current_session')->id,
                            'term_id' => config('app.current_term')->id,
                            'date' => now(),
                            'status' => 'present',
                            'recorded_by' => Auth::id(),
                        ]);

                        $summary = \App\Models\AttendanceSummary::calculateForStudent(
                            $record,
                            config('app.current_session')->id,
                            config('app.current_term')->id
                        );

                        // dd($summary);

                        Notification::make()
                            ->success()
                            ->title('Present')
                            ->body("{$record->full_name} marked present")
                            ->send();
                    }),

                Action::make('markAbsent')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Absent')
                    ->modalDescription(fn(Student $record) => "Mark {$record->full_name} as absent?")
                    ->hidden(function (Student $record): bool {
                        return AttendanceRecord::where([
                            'student_id' => $record->id,
                            'date' => now()->toDateString(),
                            'academic_session_id' => config('app.current_session')->id,
                            'term_id' => config('app.current_term')->id,
                        ])->exists();
                    })
                    ->form([
                        Textarea::make('remarks')
                            ->label('Reason for Absence'),
                    ])
                    ->action(function (Student $record, array $data): void {
                        AttendanceRecord::create([
                            'school_id' => Filament::getTenant()->id,
                            'class_room_id' => $record->class_room_id,
                            'student_id' => $record->id,
                            'academic_session_id' => config('app.current_session')->id,
                            'term_id' => config('app.current_term')->id,
                            'date' => now(),
                            'status' => 'absent',
                            'remarks' => $data['remarks'],
                            'recorded_by' => Auth::id(),
                        ]);

                        \App\Models\AttendanceSummary::calculateForStudent(
                            $record,
                            config('app.current_session')->id,
                            config('app.current_term')->id
                        );

                        Notification::make()
                            ->success()
                            ->title('Absent')
                            ->body("{$record->full_name} marked absent")
                            ->send();
                    }),

                Action::make('markLate')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Late')
                    ->modalDescription(fn(Student $record) => "Mark {$record->full_name} as late?")
                    ->hidden(function (Student $record): bool {
                        return AttendanceRecord::where([
                            'student_id' => $record->id,
                            'date' => now()->toDateString(),
                            'academic_session_id' => config('app.current_session')->id,
                            'term_id' => config('app.current_term')->id,
                        ])->exists();
                    })
                    ->form([
                        TimePicker::make('arrival_time')
                            ->label('Arrival Time')
                            ->required()
                            ->native(false),
                        Textarea::make('remarks')
                            ->label('Reason for Late Arrival')
                            ->required(),
                    ])
                    ->action(function (Student $record, array $data): void {
                        AttendanceRecord::create([
                            'school_id' => Filament::getTenant()->id,
                            'class_room_id' => $record->class_room_id,
                            'student_id' => $record->id,
                            'academic_session_id' => config('app.current_session')->id,
                            'term_id' => config('app.current_term')->id,
                            'date' => now(),
                            'status' => 'late',
                            'arrival_time' => $data['arrival_time'],
                            'remarks' => $data['remarks'],
                            'recorded_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Late')
                            ->body("{$record->full_name} marked late")
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('markAllPresent')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Selected Students Present')
                    ->action(function (Collection $records): void {
                        try {
                            $currentSession = config('app.current_session');
                            $currentTerm = config('app.current_term');
                            $tenant = Filament::getTenant();

                            if (!$currentSession || !$currentTerm || !$tenant) {
                                throw new \Exception('Required configuration is missing');
                            }

                            $marked = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                $exists = AttendanceRecord::where([
                                    'student_id' => $record->id,
                                    'date' => now()->toDateString(),
                                    'academic_session_id' => $currentSession->id,
                                    'term_id' => $currentTerm->id,
                                ])->exists();

                                if (!$exists) {
                                    AttendanceRecord::create([
                                        'school_id' => $tenant->id,
                                        'class_room_id' => $record->class_room_id,
                                        'student_id' => $record->id,
                                        'academic_session_id' => $currentSession->id,
                                        'term_id' => $currentTerm->id,
                                        'date' => now(),
                                        'status' => 'present',
                                        'recorded_by' => Auth::id(),
                                    ]);

                                    // Calculate attendance summary for each student
                                    \App\Models\AttendanceSummary::calculateForStudent(
                                        $record,
                                        $currentSession->id,
                                        $currentTerm->id
                                    );

                                    $marked++;
                                } else {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Bulk Attendance Marked')
                                ->body("{$marked} students marked present. {$skipped} already marked.")
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Error in bulk mark present: ' . $e->getMessage());
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to mark attendance. Please try again.')
                                ->send();
                        }
                    }),

                BulkAction::make('markAllAbsent')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Selected Students Absent')
                    ->form([
                        Textarea::make('remarks')
                            ->label('Reason for Absence')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        try {
                            $currentSession = config('app.current_session');
                            $currentTerm = config('app.current_term');
                            $tenant = Filament::getTenant();

                            if (!$currentSession || !$currentTerm || !$tenant) {
                                throw new \Exception('Required configuration is missing');
                            }

                            $marked = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                $exists = AttendanceRecord::where([
                                    'student_id' => $record->id,
                                    'date' => now()->toDateString(),
                                    'academic_session_id' => $currentSession->id,
                                    'term_id' => $currentTerm->id,
                                ])->exists();

                                if (!$exists) {
                                    AttendanceRecord::create([
                                        'school_id' => $tenant->id,
                                        'class_room_id' => $record->class_room_id,
                                        'student_id' => $record->id,
                                        'academic_session_id' => $currentSession->id,
                                        'term_id' => $currentTerm->id,
                                        'date' => now(),
                                        'status' => 'absent',
                                        'remarks' => $data['remarks'],
                                        'recorded_by' => Auth::id(),
                                    ]);

                                    // Calculate attendance summary for each student
                                    \App\Models\AttendanceSummary::calculateForStudent(
                                        $record,
                                        $currentSession->id,
                                        $currentTerm->id
                                    );

                                    $marked++;
                                } else {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Bulk Attendance Marked')
                                ->body("{$marked} students marked absent. {$skipped} already marked.")
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Error in bulk mark absent: ' . $e->getMessage());
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to mark attendance. Please try again.')
                                ->send();
                        }
                    }),
            ]);
    }
}
