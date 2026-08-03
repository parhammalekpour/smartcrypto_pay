<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            // Audit log: email verified
            try {
                DB::table('audit_logs')->insert([
                    'actor_id' => $request->user()->id,
                    'user_id' => $request->user()->id,
                    'action' => 'email_verified',
                    'resource_type' => 'user',
                    'resource_id' => $request->user()->id,
                    'diff' => json_encode([
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // swallow logging errors to avoid breaking verification flow
            }
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
