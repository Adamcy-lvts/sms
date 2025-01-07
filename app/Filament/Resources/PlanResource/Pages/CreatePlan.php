<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Models\Plan;
use Filament\Actions;
use App\Helpers\PaystackHelper;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\PlanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function handleRecordCreation(array $data): Model
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

                // Create in Paystack with discounted price
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

            // Create plan in Paystack
            $paystackResponse = PaystackHelper::createPlan($paystackData);

            if (!$paystackResponse['status']) {
                throw new \Exception($paystackResponse['message'] ?? 'Failed to create plan in Paystack');
            }

            // Create local record with features as array
            $plan = Plan::create([
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
                'plan_code' => $paystackResponse['data']['plan_code'] ?? null,
            ]);

            Notification::make()
                ->title('Plan created successfully')
                ->success()
                ->send();

            return $plan;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error creating plan')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
