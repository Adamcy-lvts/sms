<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Widgets;

use Filament\Tables;
use App\Models\Teacher;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\TableWidget as BaseWidget;

class TeachersTable extends BaseWidget
{
    public ?Model $record = null;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Teacher::query()->whereHas(
                'classRooms',
                fn($q) =>
                $q->where('class_room_id', $this->record->id)
            ))
            ->columns([
                Tables\Columns\ImageColumn::make('staff.profile_picture_url')->circular()->label('Profile Picture'),
                Tables\Columns\TextColumn::make('staff.full_name')->label('Full Name'),
                Tables\Columns\TextColumn::make('subjects.name'),
                Tables\Columns\TextColumn::make('staff.phone_number'),
            ]);
    }
}


