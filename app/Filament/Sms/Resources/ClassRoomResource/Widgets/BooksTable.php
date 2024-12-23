<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Widgets;

use App\Models\Book;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\TableWidget as BaseWidget;

class BooksTable extends BaseWidget
{
    public ?Model $record = null;
    
    public function table(Table $table): Table
    {
        return $table
            ->query(Book::query()->where('class_room_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('subject.name'),
                Tables\Columns\TextColumn::make('author'),
                Tables\Columns\TextColumn::make('publisher'),
                Tables\Columns\TextColumn::make('edition')
            ]);
    }
}
