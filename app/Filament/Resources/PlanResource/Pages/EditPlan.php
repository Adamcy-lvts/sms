<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Models\Plan;
use Filament\Actions;
use App\Helpers\PaystackHelper;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\PlanResource;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mount($record): void
    {
        $this->record = Plan::find($record);



        $this->form->fill([
            'name' => $this->record->name,
            'interval' => $this->record->interval,
            'price' => $this->record->price,
            'duration' => $this->record->duration,
            'description' => $this->record->description,
            'features' => $this->record->features,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $datas = [
            'name' => $data['name'],
            'interval' => $data['interval'],
            'amount' => $data['price'] * 100
        ];
        $paystackResponse = PaystackHelper::updatePlan($record->plan_code, $datas);

        $record->update([
            'name' => $data['name'],
            'interval' => $data['interval'],
            'price' => $data['price'],
            'duration' => $data['duration'],
            'description' => $data['description'],
            'features' => $data['features'],
        ]);

        return $record;
    }
}
