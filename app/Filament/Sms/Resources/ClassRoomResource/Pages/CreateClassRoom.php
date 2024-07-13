<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Pages;

use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\ClassRoomResource;

class CreateClassRoom extends CreateRecord
{
    protected static string $resource = ClassRoomResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $classRoomData = [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'capacity' => $data['capacity'],

        ];

        $record = new ($this->getModel())($classRoomData);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            return $this->associateRecordWithTenant($record, $tenant);
        }

        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
