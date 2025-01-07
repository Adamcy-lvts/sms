<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\ReportCard;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentReportCardHistoryTable extends BaseWidget
{
    public ?Student $student = null;

    public function mount(?Student $student = null)
    {
        $this->student = $student ?? Filament::getTenant();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ReportCard::query()
                    ->where('student_id', $this->student->id)
                    ->latest()
            )
            ->columns([
                TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable(),

                TextColumn::make('term.name')
                    ->sortable(),

                TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Position')
                    ->formatStateUsing(fn($state, $record) => "{$state} of {$record->class_size}")
                    ->sortable(),

                TextColumn::make('average_score')
                    ->label('Average')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . '%')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state >= 70 => 'success',
                        $state >= 60 => 'info',
                        $state >= 50 => 'warning',
                        default => 'danger'
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'published',
                        'danger' => 'draft'
                    ]),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->label('Academic Session')
                    ->preload()
                    ->multiple(),

                SelectFilter::make('term_id')
                    ->relationship('term', 'name')
                    ->label('Term')
                    ->preload()
                    ->multiple(),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'published' => 'Published',
                    ])
                    ->multiple(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No report cards')
            ->emptyStateDescription('No report cards have been generated yet for this student.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->persistFiltersInSession()
            ->persistSortInSession();
    }
}
