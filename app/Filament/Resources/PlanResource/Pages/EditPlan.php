<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Models\Plan;
use Filament\Actions;
use App\Models\Feature;
use App\Helpers\PaystackHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\PlanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    public function mount($record): void
    {
        $this->record = Plan::with('features')->findOrFail($record);

        if (!$this->record->relationLoaded('features')) {
            $this->record->load('features');
        }

        $formData = $this->record->attributesToArray();
        $formData['features'] = $this->record->features?->pluck('id')->toArray() ?? [];
        
        // No need to extract limits from pivot table anymore
        // Just use the direct columns
        $formData['max_students'] = $this->record->max_students;
        $formData['max_staff'] = $this->record->max_staff;
        $formData['max_classes'] = $this->record->max_classes;

        Log::info('Form data prepared:', [
            'features' => $formData['features'],
            'limits' => [
                'students' => $formData['max_students'],
                'staff' => $formData['max_staff'],
                'classes' => $formData['max_classes'],
            ]
        ]);

        $this->form->fill($formData);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => $this->record->activeSubscriptions()->count() === 0),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            // Handle pricing updates
            if ($data['interval'] === 'annually') {
                $data['discounted_price'] = $data['price'] - 
                    ($data['price'] * ($data['yearly_discount'] ?? 0)) / 100;
                $paystackAmount = $data['discounted_price'] * 100;
            } else {
                $data['discounted_price'] = null;
                $paystackAmount = $data['price'] * 100;
            }
            

            // Update plan in Paystack
            PaystackHelper::updatePlan($record->plan_code, [
                'name' => $data['name'],
                'amount' => $paystackAmount
            ]);

            // Update features
            if (isset($data['features'])) {
                $record->features()->sync($data['features']);
            }

            // Update plan attributes including direct limit columns
            $record->update([
                'name' => $data['name'],
                'interval' => $data['interval'],
                'price' => $data['price'],
                'discounted_price' => $data['discounted_price'] ?? 0,
                'duration' => $data['duration'],
                'description' => $data['description'],
                'trial_period' => $data['trial_period'] ?? 0,
                'has_trial' => $data['has_trial'] ?? false,
                'yearly_discount' => $data['yearly_discount'] ?? 0,
                'status' => $data['status'],
                'badge_color' => $data['badge_color'],
                'cto' => $data['cto'],
                'max_students' => $data['max_students'],
                'max_staff' => $data['max_staff'],
                'max_classes' => $data['max_classes'],
            ]);

            Notification::make()
                ->title('Plan updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (\Exception $e) {
            Log::error('Plan update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Update Error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    private function updatePricing(Plan $plan, array $data): void
    {
        if ($data['interval'] === 'annually') {
            $plan->discounted_price = $data['price'] - ($data['price'] * ($data['yearly_discount'] / 100));
            $paystackAmount = $plan->discounted_price * 100;
        } else {
            $plan->discounted_price = null;
            $paystackAmount = $data['price'] * 100;
        }

        $response = PaystackHelper::updatePlan($plan->plan_code, [
            'name' => $data['name'],
            'amount' => $paystackAmount
        ]);

        if (!$response['status']) {
            throw new \Exception("Paystack update failed: " . ($response['message'] ?? 'Unknown error'));
        }
    }

    private function processFeatures(array $data): array
    {
        $featuresToSync = [];

        if (!empty($data['features'])) {
            foreach ($data['features'] as $featureId) {
                $feature = Feature::find($featureId);
                if (!$feature) continue;

                $limits = $this->determineLimits($feature, $data);
                $featuresToSync[$featureId] = ['limits' => $limits];

                Log::debug('Processed feature', [
                    'feature_id' => $featureId,
                    'limits' => $limits ?? 'null'
                ]);
            }
        }

        return $featuresToSync;
    }

    private function determineLimits(Feature $feature, array $data): ?array
    {
        if (!$feature->is_limitable) return null;

        $limits = match ($feature->slug) {
            'students_limit' => [
                'student_limit' => isset($data['featureLimits']['max_students'])
                    ? (int)$data['featureLimits']['max_students']
                    : null
            ],
            'staff_limit' => [
                'staff_limit' => isset($data['featureLimits']['max_staff'])
                    ? (int)$data['featureLimits']['max_staff']
                    : null
            ],
            'classes_limit' => [
                'class_limit' => isset($data['featureLimits']['max_classes'])
                    ? (int)$data['featureLimits']['max_classes']
                    : null
            ],
            default => null,
        };

        // Filter out null values
        return $limits ? array_filter($limits) : null;
    }

    private function cleanPlanData(array $data): array
    {
        return collect($data)->only([
            'name',
            'interval',
            'price',
            'duration',
            'description',
            'trial_period',
            'has_trial',
            'yearly_discount',
            'status',
            'badge_color',
            'cto'
        ])->toArray();
    }
}
