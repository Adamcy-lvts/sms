<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

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
}
