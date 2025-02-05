<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Widgets;

use Filament\Tables;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\TableWidget as BaseWidget;

class SubjectsTable extends BaseWidget
{

    public ?Model $record = null;
    
    public function table(Table $table): Table
    {
        $query = Subject::query()
            ->whereHas('classRooms', function($q) {
                $q->where('class_room_id', $this->record->id);
            });

        // Filter by teacher if user is a teacher
        if (auth()->user()->hasRole('teacher')) {
            $teacher = Teacher::where('staff_id', auth()->user()->staff->id)->first();
            
            if ($teacher) {
                $query->whereHas('teachers', function($q) use ($teacher) {
                    $q->where('teachers.id', $teacher->id);
                });
            }
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('teachers.staff.full_name'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\IconColumn::make('is_active')
            ]);
    }
}
