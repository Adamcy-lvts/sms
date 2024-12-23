<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Widgets;

use Filament\Tables;
use App\Models\Subject;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\TableWidget as BaseWidget;

class SubjectsTable extends BaseWidget
{

    public ?Model $record = null;
    
    public function table(Table $table): Table
    {
        return $table
            ->query(Subject::query()->whereHas(
                'classRooms',
                fn($q) =>
                $q->where('class_room_id', $this->record->id)
            ))
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('teachers.staff.full_name'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\IconColumn::make('is_active')
            ]);
    }
}
