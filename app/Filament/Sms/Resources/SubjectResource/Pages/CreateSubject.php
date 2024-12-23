<?php

namespace App\Filament\Sms\Resources\SubjectResource\Pages;

use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\SubjectResource;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $subjectData = [
            'name' => $data['name'],
            'name_ar' => $data['name_ar'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'],
            'position' => $data['position'],
            'color' => $data['color'],
            'is_optional' => $data['is_optional'],
            'is_active' => $data['is_active'],
        ];


        $record = new ($this->getModel())($subjectData);

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
