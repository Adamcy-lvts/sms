<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaystackHelper
{
    public static function createSubAccount($data)
    {
        $url = "https://api.paystack.co/subaccount";
        $fields_string = http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . config('services.paystack.secret'),
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('cURL error: ' . curl_error($ch));
            return null;
        }

        curl_close($ch);

        return json_decode($result, true);
    }

    // Create a plan on Paystack.

    public static function createPlan($data)
    {
        $url = "https://api.paystack.co/plan";
        $fields_string = http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . config('services.paystack.secret'),
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return json_decode($result, true);
    }

    // Update a plan on Paystack.
    public static function updatePlan($idOrCode, $data)
    {
        $url = "https://api.paystack.co/plan/" . $idOrCode;
        $fields_string = http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . config('services.paystack.secret'),
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return json_decode($result, true);
    }

    public static function createSubscription($data)
    {
        $url = "https://api.paystack.co/subscription";
        $fields_string = http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . config('services.paystack.secret'),
            "Cache-Control: no-cache",
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return json_decode($result, true);
    }


    public static function getCustomerSubscriptions($customer_id)
    {
        $apiKey = config('services.paystack.secret');
        $baseUrl = 'https://api.paystack.co/subscription?customer=';

        try {
            $response = Http::withToken($apiKey)
                ->get($baseUrl . $customer_id);

            if ($response->successful()) {
                return $response->json();
            }

            // Log error details if the request failed
            Log::error('Failed to fetch customer subscriptions', [
                'customer_id' => $customer_id,
                'error' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            // Log the exception details
            Log::error('Exception occurred while fetching customer subscriptions', [
                'customer_id' => $customer_id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }


    // public static function createPlan($name, $interval, $amount)

    // {
    //     $apiKey = config('services.paystack.secret');
    //     $url = "https://api.paystack.co/plan";

    //     $fields = [
    //         'name' => $name,
    //         'interval' => $interval,
    //         'amount' => $amount
    //     ];

    //     $fields_string = http_build_query($fields);

    //     try {
    //         $ch = curl_init();

    //         curl_setopt($ch, CURLOPT_URL, $url);
    //         curl_setopt($ch, CURLOPT_POST, true);
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    //         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //             "Authorization: Bearer " . $apiKey,
    //             "Cache-Control: no-cache",
    //         ));
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //         $result = curl_exec($ch);
    //         curl_close($ch);

    //         if (!$result) {
    //             throw new \Exception('Failed to create plan');
    //         }

    //         return json_decode($result, true);
    //     } catch (\Exception $e) {
    //         Log::error('Exception occurred while creating plan', [
    //             'name' => $name,
    //             'interval' => $interval,
    //             'amount' => $amount,
    //             'error' => $e->getMessage()
    //         ]);

    //         return null;
    //     }
    // }
}
