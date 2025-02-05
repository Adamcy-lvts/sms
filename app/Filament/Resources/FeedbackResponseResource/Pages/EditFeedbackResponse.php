<?php

namespace App\Filament\Resources\FeedbackResponseResource\Pages;

use App\Filament\Resources\FeedbackResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeedbackResponse extends EditRecord
{
    protected static string $resource = FeedbackResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
