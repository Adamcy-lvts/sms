<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function pricing()
    {
        $plans = Plan::active()->get();
        return view('pricing', compact('plans'));
    }
}
