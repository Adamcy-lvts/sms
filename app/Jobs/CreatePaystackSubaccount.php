<?php

namespace App\Jobs;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use App\Helpers\PaystackHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreatePaystackSubaccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $agent;
    protected $subaccountData;

    public function __construct(Agent $agent, $subaccountData)
    {
        $this->agent = $agent;
        $this->subaccountData = $subaccountData;
    }

    public function handle()
    {
      
      
        try {
            $subaccount = PaystackHelper::createSubAccount($this->subaccountData);

            $this->agent->update(['subaccount_code' => $subaccount['data']['subaccount_code']]);

        } catch (\TypeError $te) {
            Log::error("Caught type error: " . $te->getMessage());
        } catch (\Exception $e) {
            Log::error("Caught general exception: " . $e->getMessage());
        }
    }

}
