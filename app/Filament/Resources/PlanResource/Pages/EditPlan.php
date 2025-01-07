<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Models\Plan;
use Filament\Actions;
use App\Helpers\PaystackHelper;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\PlanResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => $this->record->activeSubscriptions()->count() === 0),
        ];
    }

    public function mount($record): void
    {
        $this->record = Plan::findOrFail($record);

        abort_unless($this->record->exists, 404);

        $this->form->fill($this->record->only([
            'name',
            'interval',
            'price',
            'duration',
            'description',
            'features',
            'trial_period',
            'has_trial',
            'yearly_discount',
            'max_students',
            'max_staff',
            'max_classes',
            'status',
            'badge_color',
            'cto',
        ]));
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            // Convert features string to array if needed
            $features = is_string($data['features']) ?
                array_map('trim', explode(',', $data['features'])) : ($data['features'] ?? []);

            // For annual plans, calculate discounted price

            if ($data['interval'] === 'annually') {
                $basePrice = $data['price'];
                $discount = ($basePrice * ($data['yearly_discount'] ?? 0)) / 100;
                $data['discounted_price'] = $basePrice - $discount;
                // Update Paystack with discounted price
                $paystackData = [
                    'name' => $data['name'],
                    'interval' => $data['interval'],
                    'amount' => $data['discounted_price'] * 100
                ];
            } else {
                $data['discounted_price'] = null;
                $paystackData = [
                    'name' => $data['name'],
                    'interval' => $data['interval'],
                    'amount' => $data['price'] * 100
                ];
            }


            $paystackResponse = PaystackHelper::updatePlan($record->plan_code, $paystackData);

            if (!$paystackResponse['status']) {
                throw new \Exception($paystackResponse['message'] ?? 'Failed to update plan in Paystack');
            }

            // Update record
            $record->update([
                'name' => $data['name'],
                'interval' => $data['interval'],
                'price' => $data['price'],
                'discounted_price' => $data['discounted_price'] ?? 0,
                'duration' => $data['duration'],
                'description' => $data['description'],
                'features' => array_values(array_filter($features)), // Clean and store as array
                'trial_period' => $data['trial_period'] ?? 0,
                'has_trial' => $data['has_trial'] ?? false,
                'yearly_discount' => $data['yearly_discount'] ?? 0,
                'max_students' => $data['max_students'],
                'max_staff' => $data['max_staff'],
                'max_classes' => $data['max_classes'],
                'status' => $data['status'],
                'badge_color' => $data['badge_color'],
                'cto' => $data['cto'],
            ]);

            Notification::make()
                ->title('Plan updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error updating plan')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
