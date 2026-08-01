<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            if (auth()->user()->isMerchant()) {
                return redirect()->route('merchant.dashboard');
            }

            if (auth()->user()->isUser()) {
                return redirect()->route('user.dashboard');
            }

            return redirect()->route('dashboard');
        }

        $activeTab = 'login';

        if (
            $request->routeIs('register') ||
            $request->routeIs('landing.register.form') ||
            $request->query('tab') === 'register'
        ) {
            $activeTab = 'register';
        }

        return view('auth.landing', compact('activeTab'));
    }
}
