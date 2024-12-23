<?php

namespace App\Filament\Sms\Resources\ReportTemplateResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\ReportTemplateResource;

class CreateReportTemplate extends CreateRecord
{
    protected static string $resource = ReportTemplateResource::class;

    // Add this method to ensure proper casting
    // protected function handleRecordCreation(array $data): Model
    // {
    //     $record = static::getModel()::create($data);
    //     $record->activities_config = $data['activities_config'] ?? [];
    //     dd($data);
    //     $record->save();
        
    //     return $record;
    // }
}
