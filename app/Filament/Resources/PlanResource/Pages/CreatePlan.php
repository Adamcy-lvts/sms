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

    public function handleRecordCreation(array $data): Model
    {
        // Extract necessary data
        $name = $data['name'];
        $interval = $data['interval'];
        $amount = $data['price'];

        $datas = [
            'name' => $name,
            'interval' => $interval,
            'amount' => $amount
        ];

        try {
            // Call Paystack API to create a plan
            $paystackResponse = PaystackHelper::createPlan($datas);

            // Check if the Paystack call was successful
            if ($paystackResponse && isset($paystackResponse['status']) && $paystackResponse['status']) {
                // Create a new Plan instance and save it to the database
                $plan = new Plan();
                $plan->name = $name;
                $plan->duration = $data['duration'];
                $plan->price = $amount;
                $plan->description = $data['description'];
                $plan->features = $data['features'];
                $plan->plan_code = $paystackResponse['data']['plan_code']; // Assuming Paystack returns a plan code
                $plan->save();

                return $plan;
            } else {
                // Log the error
                \Illuminate\Support\Facades\Log::error('Paystack plan creation failed: ' . json_encode($paystackResponse));
                // Return a user-friendly error message
                return Notification::make()
                    ->title('Failed to create plan. Please try again later.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            // Log the exception
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            // Return a user-friendly error message
            return Notification::make()
            ->title('An unexpected error occurred. Please try again later.')
            ->danger()
            ->send();
            
        }
    }
}
