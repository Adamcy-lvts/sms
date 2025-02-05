<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Status;
use App\Models\Student;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Models\StudentMovement;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use App\Services\StudentStatusService;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';
    protected static bool $isLazy = false;

    private function getCurrentSessionId(): ?int
    {
        $session = config('app.current_session');
        return $session ? $session->id : null;
    }

    private function getCurrentTermId(): ?int
    {
        $term = config('app.current_term');
        return $term ? $term->id : null;
    }

    public function table(Table $table): Table
    {
        $activeStatusId = Status::where('type', 'student')
            ->where('name', 'active')
            ->first()?->id;

        return $table
            ->recordTitleAttribute('full_name')
            ->columns([

                // Hidden on mobile, can be toggled
                ImageColumn::make('profile_picture')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn() => asset('img/default.jpg')),
                // Always visible
                TextColumn::make('full_name')
                    ->label('Student Name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(),

                TextColumn::make('todayAttendance')
                    ->label('Today\'s Attendance')
                    ->state(function (Student $record): string {
                        $attendance = \App\Models\AttendanceRecord::where([
                            'student_id' => $record->id,
                            'date' => now()->toDateString(),
                        ])->first();

                        if (!$attendance) return '-';

                        $status = $attendance->status;

                        // Add an indicator if the attendance was modified
                        if ($attendance->modified_by) {
                            $status .= ' (edited)';
                        }

                        return $status;
                    })
                    ->badge()
                    ->color(fn(string $state): string => match (explode(' ', $state)[0]) {
                        'present' => 'success',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(function (Student $record): ?string {
                        $attendance = \App\Models\AttendanceRecord::where([
                            'student_id' => $record->id,
                            'date' => now()->toDateString(),
                        ])->first();

                        if (!$attendance || !$attendance->modified_by) {
                            return null;
                        }

                        return "Modified by: {$attendance->modifiedBy->name}\nReason: {$attendance->remarks}";
                    }),



                // Visible from md breakpoint up
                TextColumn::make('admission.admission_number')
                    ->label('Admission No.')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),

                // Visible from md breakpoint up
                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'expelled' => 'danger',
                        'transferred' => 'warning',
                        'deceased' => 'gray',
                        'promoted' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->visibleFrom('md'),

                // Hidden by default, can be toggled
                TextColumn::make('admission.guardian_name')
                    ->label('Guardian')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn(Student $record): string => $record->admission?->guardian_phone_number ?? '')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->multiple()
                    ->options(fn() => Status::where('type', 'student')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['values']),
                            fn (Builder $query): Builder => $query
                                ->whereIn('status_id', $data['values'])
                        );
                    })
                    ->default([$activeStatusId]),
            ])
            ->headerActions([
                // Mark All Present Action
                Action::make('markAllPresent')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->label('Mark All Present')
                    ->visible(fn(): bool => auth()->user()->can('can_take_attendance_class_room'))
                    ->requiresConfirmation()
                    ->modalHeading('Mark Entire Class Present')
                    ->modalDescription('Are you sure you want to mark all students present?')
                    ->action(function () {
                        $students = $this->getOwnerRecord()->students;
                        $marked = 0;
                        $skipped = 0;

                        foreach ($students as $student) {
                            // // Early return if session or term not set

                            // Check for existing attendance
                            $exists = \App\Models\AttendanceRecord::where([
                                'student_id' => $student->id,
                                'date' => now()->toDateString(),
                                'academic_session_id' => config('app.current_session')->id ?? null,
                                'term_id' => config('app.current_term')->id ?? null,
                            ])->exists();

                            if (!$exists) {
                                \App\Models\AttendanceRecord::create([
                                    'school_id' => Filament::getTenant()->id,
                                    'class_room_id' => $student->class_room_id,
                                    'student_id' => $student->id,
                                    'academic_session_id' => config('app.current_session')->id  ?? null,
                                    'term_id' => config('app.current_term')->id ?? null,
                                    'date' => now(),
                                    'status' => 'present',
                                    'recorded_by' => Auth::id(),
                                ]);
                                $marked++;
                            } else {
                                $skipped++;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Attendance Marked')
                            ->body("{$marked} students marked present. {$skipped} already marked.")
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('quickPresent')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip('Mark Present')
                    ->button()
                    ->size('xs')
                    ->visible(fn(Student $record): bool => 
                        auth()->user()->can('can_take_attendance_class_room') && 
                        !$this->hasAttendanceForToday($record)
                    )
                    ->action(function (Student $record) {
                        try {
                            \App\Models\AttendanceRecord::create([
                                'school_id' => Filament::getTenant()->id,
                                'class_room_id' => $record->class_room_id,
                                'student_id' => $record->id,
                                'academic_session_id' => config('app.current_session')->id ?? null,
                                'term_id' => config('app.current_term')->id ?? null,
                                'date' => now()->toDateString(),
                                'status' => 'present',
                                'recorded_by' => Auth::id(),
                            ]);

                            \App\Models\AttendanceSummary::calculateForStudent(
                                $record,
                                config('app.current_session')->id,
                                config('app.current_term')->id
                            );

                            Notification::make()
                                ->success()
                                ->title("{$record->full_name} marked present")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Attendance already marked')
                                ->send();
                        }
                    }),

                // Quick Absent Button
                Action::make('quickAbsent')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->tooltip('Mark Absent')
                    ->button()
                    ->size('xs')
                    ->visible(fn(Student $record): bool => 
                        auth()->user()->can('can_take_attendance_class_room') && 
                        !$this->hasAttendanceForToday($record)
                    )
                    ->action(function (Student $record) {
                        try {
                            \App\Models\AttendanceRecord::create([
                                'school_id' => Filament::getTenant()->id,
                                'class_room_id' => $record->class_room_id,
                                'student_id' => $record->id,
                                'academic_session_id' => config('app.current_session')->id ?? null,
                                'term_id' => config('app.current_term')->id ?? null,
                                'date' => now()->toDateString(),
                                'status' => 'absent',
                                'recorded_by' => Auth::id(),
                            ]);

                            \App\Models\AttendanceSummary::calculateForStudent(
                                $record,
                                config('app.current_session')->id ?? null,
                                config('app.current_term')->id  ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title("{$record->full_name} marked absent")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Attendance already marked')
                                ->send();
                        }
                    }),

                ActionGroup::make([
                    // Edit Attendance Button - Only shows when attendance exists
                    Action::make('editAttendance')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->tooltip('Edit Attendance')
                        ->visible(fn(Student $record): bool => 
                            auth()->user()->can('can_take_attendance_class_room') && 
                            $this->hasAttendanceForToday($record)
                        )
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Update Status')
                                ->options([
                                    'present' => 'Present',
                                    'absent' => 'Absent',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('remarks')
                                ->label('Reason for Change')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (Student $record, array $data) {
                            $attendance = \App\Models\AttendanceRecord::where([
                                'student_id' => $record->id,
                                'date' => now()->toDateString(),
                            ])->first();

                            $oldStatus = $attendance->status;

                            $attendance->update([
                                'status' => $data['status'],
                                'remarks' => $data['remarks'],
                                'modified_by' => Auth::id(),
                            ]);

                            // Recalculate attendance summary
                            \App\Models\AttendanceSummary::calculateForStudent(
                                $record,
                                config('app.current_session')->id ?? null,
                                config('app.current_term')->id ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title('Attendance Updated')
                                ->body("Status changed from {$oldStatus} to {$data['status']}")
                                ->send();
                        }),
                    Action::make('markPresent')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->hidden(function (Student $record): bool {
                            if (!config('app.current_session') || !config('app.current_term')) {
                                return true;
                            }
                            return \App\Models\AttendanceRecord::where([
                                'student_id' => $record->id,
                                'date' => now()->toDateString(),
                                'academic_session_id' => config('app.current_session')->id ?? null,
                                'term_id' => config('app.current_term')->id ?? null,
                            ])->exists();
                        })
                        ->action(function (Student $record): void {
                            \App\Models\AttendanceRecord::create([
                                'school_id' => Filament::getTenant()->id,
                                'class_room_id' => $record->class_room_id,
                                'student_id' => $record->id,
                                'academic_session_id' => config('app.current_session')->id ?? null,
                                'term_id' => config('app.current_term')->id ?? null,
                                'date' => now(),
                                'status' => 'present',
                                'recorded_by' => Auth::id(),
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Present')
                                ->body("{$record->full_name} marked present")
                                ->send();
                        }),

                    // Mark Absent Action
                    Action::make('markAbsent')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->hidden(function (Student $record): bool {
                            if (!config('app.current_session') || !config('app.current_term')) {
                                return true;
                            }
                            return \App\Models\AttendanceRecord::where([
                                'student_id' => $record->id,
                                'date' => now()->toDateString(),
                                'academic_session_id' => config('app.current_session')->id,
                                'term_id' => config('app.current_term')->id ?? null,
                            ])->exists();
                        })
                        ->action(function (Student $record): void {
                            \App\Models\AttendanceRecord::create([
                                'school_id' => Filament::getTenant()->id,
                                'class_room_id' => $record->class_room_id,
                                'student_id' => $record->id,
                                'academic_session_id' => config('app.current_session')->id,
                                'term_id' => config('app.current_term')->id ?? null,
                                'date' => now(),
                                'status' => 'absent',
                                'recorded_by' => Auth::id(),
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Absent')
                                ->body("{$record->full_name} marked absent")
                                ->send();
                        }),

                    // Promote Student Action
                    Action::make('promote')
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('info')
                        ->visible(fn(): bool => auth()->user()->can('promote_student'))
                        ->form([
                            Forms\Components\Select::make('class_room_id')
                                ->label('New Class')
                                ->options(ClassRoom::pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Textarea::make('note')
                                ->label('Promotion Note')
                                ->maxLength(255),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Promote Student')
                        ->modalDescription(fn(Student $record) => "Are you sure you want to promote {$record->full_name}?")
                        ->action(function (Student $record, array $data): void {
                            DB::transaction(function () use ($record, $data) {
                                $oldClassId = $record->class_room_id;
                                $currentSession = config('app.current_session');
                                $nextSession = AcademicSession::where('start_date', '>', $currentSession->end_date)
                                    ->orderBy('start_date')
                                    ->first();
                                $newStatus = Status::find($data['status_id'])->name;
                                $record->update([
                                    'class_room_id' => $data['class_room_id'],
                                ]);

                                StudentMovement::create([
                                    'school_id' => Filament::getTenant()->id,
                                    'student_id' => $record->id,
                                    'from_class_id' => $oldClassId,
                                    'to_class_id' => $data['class_room_id'],
                                    'from_session_id' => $currentSession->id,
                                    'to_session_id' => $nextSession->id ?? $currentSession->id,
                                    'movement_type' => 'promotion',
                                    'movement_date' => now(),
                                    'reason' => $data['note'] ?? 'Individual promotion to new class',
                                    'status' => 'completed',  // Add this
                                    'processed_by' => auth()->id() // Add this
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Student Promoted')
                                    ->body('The student has been promoted successfully.')
                                    ->send();
                            });
                        }),

                    // Change Status Action
                    Tables\Actions\Action::make('changeStatus')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn(): bool => auth()->user()->can('change_status_student'))
                        ->form([
                            Forms\Components\Select::make('status_id')
                                ->label('New Status')
                                ->native(false)
                                ->options(fn() => Status::where('type', 'student')
                                    ->whereNotIn('name', ['Promoted']) // Exclude 'Promoted' status
                                    ->pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for Status Change')
                                ->maxLength(255)
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Change Student Status')
                        ->modalDescription(fn(Student $record) => "Change status for {$record->full_name}")
                        ->action(function (Student $record, array $data): void {
                            $statusService = new StudentStatusService();
                            
                            try {
                                $statusService->changeStatus(
                                    student: $record,
                                    newStatusId: $data['status_id'],
                                    reason: $data['reason'] ?? 'No reason provided'
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Status Updated')
                                    ->body('The student status has been updated successfully.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to update student status: ' . $e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\EditAction::make()
                        ->visible(fn(): bool => auth()->user()->can('update_student')),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(): bool => auth()->user()->can('delete_student')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Mark Present
                    BulkAction::make('bulkMarkPresent')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(): bool => auth()->user()->can('can_take_attendance_class_room'))
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $marked = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                $exists = \App\Models\AttendanceRecord::where([
                                    'student_id' => $record->id,
                                    'date' => now()->toDateString(),
                                    'academic_session_id' => config('app.current_session')->id,
                                    'term_id' => config('app.current_term')->id ?? null,
                                ])->exists();

                                if (!$exists) {
                                    \App\Models\AttendanceRecord::create([
                                        'school_id' => Filament::getTenant()->id,
                                        'class_room_id' => $record->class_room_id,
                                        'student_id' => $record->id,
                                        'academic_session_id' => config('app.current_session')->id,
                                        'term_id' => config('app.current_term')->id ?? null,
                                        'date' => now(),
                                        'status' => 'present',
                                        'recorded_by' => Auth::id(),
                                    ]);
                                    $marked++;
                                } else {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Bulk Attendance Marked')
                                ->body("{$marked} students marked present. {$skipped} skipped.")
                                ->send();
                        }),

                    // Bulk Promote
                    BulkAction::make('bulkPromote')
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('info')
                        ->visible(fn(): bool => auth()->user()->can('bulk_promote_student'))
                        ->form([
                            Forms\Components\Select::make('class_room_id')
                                ->label('New Class')
                                ->options(ClassRoom::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->action(function (Collection $records, array $data): void {
                            $currentSession = config('app.current_session');
                            $nextSession = AcademicSession::where('start_date', '>', $currentSession->end_date)
                                ->orderBy('start_date')
                                ->first();
                         
                            foreach ($records as $record) {
                                $oldClassId = $records->first()->class_room_id;
                                $record->update([
                                    'class_room_id' => $data['class_room_id'],
                                ]);

                                StudentMovement::create([
                                    'school_id' => Filament::getTenant()->id,
                                    'student_id' => $record->id,
                                    'from_class_id' => $oldClassId,
                                    'to_class_id' => $data['class_room_id'],
                                    'from_session_id' => $currentSession->id,
                                    'to_session_id' => $nextSession->id ?? $currentSession->id,
                                    'movement_type' => 'promotion',
                                    'movement_date' => now(),
                                    'reason' => 'Bulk promotion to new class',
                                    'status' => 'completed',  // Add this
                                    'processed_by' => auth()->id() // Add this
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Students Promoted')
                                ->body("{$records->count()} students have been promoted.")
                                ->send();
                        }),

                    // In bulkStatusChange action
                    BulkAction::make('bulkStatusChange')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn(): bool => auth()->user()->can('bulk_status_change_student'))
                        ->form([
                            Forms\Components\Select::make('status_id')
                                ->label('New Status')
                                ->options(fn() => Status::where('type', 'student')
                                    ->whereNotIn('name', ['Promoted'])
                                    ->pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for Status Change')
                                ->maxLength(255)
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Change Status for Selected Students')
                        ->action(function (Collection $records, array $data): void {
                            $statusService = new StudentStatusService();
                            
                            DB::transaction(function () use ($records, $data, $statusService) {
                                $records->each(function ($record) use ($data, $statusService) {
                                    $statusService->changeStatus(
                                        student: $record,
                                        newStatusId: $data['status_id'],
                                        reason: $data['reason'] ?? 'No reason provided'
                                    );
                                });

                                Notification::make()
                                    ->success()
                                    ->title('Status Updated')
                                    ->body('Selected students statuses have been updated successfully.')
                                    ->send();
                            });
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()->can('delete_any_student')),
                ]),
            ]);
    }

    private function hasAttendanceForToday(Student $student): bool
    {
        return \App\Models\AttendanceRecord::where([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
        ])->exists();
    }
}
