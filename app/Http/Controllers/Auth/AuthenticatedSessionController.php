<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
 
        if (! Auth::user()->hasVerifiedEmail()) {
            $intended = session()->get('url.intended');
            $path = $intended ? parse_url($intended, PHP_URL_PATH) : null;
 
            if ($path && str_starts_with($path, '/verify-email/')) {
                return redirect()->intended(route('dashboard', absolute: false));
            }
 
            Auth::logout();
            $request->session()->regenerateToken();
 
            return redirect()->route('login')
                ->with('status', __('ایمیل شما وریفای نشده است.'));
        }
 
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
