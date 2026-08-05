<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Normalize email to lowercase to avoid validation errors caused by uppercase letters
        $request->merge(['email' => strtolower($request->input('email'))]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['sometimes', 'string', 'in:user,merchant'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
  
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->input('role', 'user'),
            'password' => Hash::make($request->password),
        ]);
 
        try {
            event(new Registered($user));
        } catch (\Throwable $exception) {
            // Do not leave a partially-created account if the verification email fails to send.
            $user->delete();

            return back()->withInput()->withErrors([
                'email' => __('Unable to send verification email. Please try again later.'),
            ]);
        }

        return redirect()->route('login')
            ->with('status', 'Registration successful. Please verify your email address before signing in.');
    }
}
