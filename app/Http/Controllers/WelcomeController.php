<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function index(): View
    {
        $monthlyPlans = Plan::monthly()->active()->get();
        $annualPlans = Plan::annually()->active()->get();
        
        return view('welcome', [
            'monthlyPlans' => $monthlyPlans,
            'annualPlans' => $annualPlans,
        ]);
    }
}

