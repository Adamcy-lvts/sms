<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Models\Plan;
use App\Models\Feature;
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
            // Calculate pricing
            if ($data['interval'] === 'annually') {
                $basePrice = $data['price'];
                $discount = ($basePrice * ($data['yearly_discount'] ?? 0)) / 100;
                $data['discounted_price'] = $basePrice - $discount;
                $paystackAmount = $data['discounted_price'] * 100;
            } else {
                $data['discounted_price'] = null;
                $paystackAmount = $data['price'] * 100;
            }

            // Create plan in Paystack
            $paystackResponse = PaystackHelper::createPlan([
                'name' => $data['name'],
                'interval' => $data['interval'],
                'amount' => $paystackAmount
            ]);

            if (!$paystackResponse['status']) {
                throw new \Exception($paystackResponse['message'] ?? 'Failed to create plan in Paystack');
            }

            // Create local plan record with direct limit columns
            $plan = Plan::create([
                'name' => $data['name'],
                'interval' => $data['interval'],
                'price' => $data['price'],
                'discounted_price' => $data['discounted_price'],
                'duration' => $data['duration'],
                'description' => $data['description'],
                'trial_period' => $data['trial_period'] ?? 0,
                'has_trial' => $data['has_trial'] ?? false,
                'yearly_discount' => $data['yearly_discount'] ?? 0,
                'status' => $data['status'],
                'badge_color' => $data['badge_color'],
                'cto' => $data['cto'],
                'plan_code' => $paystackResponse['data']['plan_code'] ?? null,
                'max_students' => $data['max_students'],
                'max_staff' => $data['max_staff'],
                'max_classes' => $data['max_classes'],
            ]);

            // Attach features (without limits in pivot)
            if (!empty($data['features'])) {
                $plan->features()->attach($data['features']);
            }

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
