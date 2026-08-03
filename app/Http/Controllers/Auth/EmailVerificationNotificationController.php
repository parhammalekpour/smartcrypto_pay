<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        // Audit log: email verification link sent
        try {
            DB::table('audit_logs')->insert([
                'actor_id' => $request->user()->id,
                'user_id' => $request->user()->id,
                'action' => 'email_verification_sent',
                'resource_type' => 'user',
                'resource_id' => $request->user()->id,
                'diff' => json_encode([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Don't break the user flow if logging fails; fail silently but could be reported to monitoring
        }

        return back()->with('status', 'verification-link-sent');
    }
}
