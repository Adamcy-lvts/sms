<?php

namespace App\Filament\Resources\FeedbackResource\Pages;

use Filament\Actions;
use App\Models\Feedback;
use Filament\Actions\Action;
use App\Events\OpenFeedbackModal;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\FeedbackResource;

class ListFeedback extends ListRecords
{
    protected static string $resource = FeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('preview')
            ->icon('heroicon-m-eye')
            ->action(function (Feedback $record) {
                event(new OpenFeedbackModal($record));
            })
            ->label('Preview Modal'),
        ];
    }
}
