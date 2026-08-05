<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationPromptController extends Controller
{
    /**
     * Redirect unverified users to the login page instead of showing the verification notice page.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }
 
        Auth::logout();
        $request->session()->regenerateToken();
 
        return redirect()->route('login')
            ->with('status', __('ایمیل شما وریفای نشده است.'));
    }
}
