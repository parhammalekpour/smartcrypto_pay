<?php

namespace App\Http\Controllers;

use App\Models\TwoFactor;
use App\Services\TOTP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $two = TwoFactor::where('user_id', $user->id)->first();

        if (!$two || !$two->secret_enc) {
            $secret = TOTP::generateSecret();
            $otpUrl = TOTP::getOtpAuthUrl(config('app.name'), $user->email, $secret);
            // Don't persist secret until user confirms
            return view('auth.2fa.setup', compact('secret', 'otpUrl'));
        }

        return view('auth.2fa.manage', ['two' => $two]);
    }

    public function enable(Request $request)
    {
        $request->validate([
            'secret' => 'required|string',
            'code' => 'required|string',
        ]);

        $user = $request->user();

        if (!TOTP::verifyCode($request->secret, $request->code, 1)) {
            return back()->withErrors(['code' => 'کد ۲FA نامعتبر است']);
        }

        $enc = Crypt::encryptString($request->secret);

        $codes = $this->generateBackupCodes();

        $two = TwoFactor::updateOrCreate(
            ['user_id' => $user->id, 'method' => 'totp'],
            ['secret_enc' => $enc, 'enabled_at' => now(), 'backup_codes' => $codes]
        );

        // audit log
        try {
            DB::table('audit_logs')->insert([
                'actor_id' => $user->id,
                'user_id' => $user->id,
                'action' => '2fa_enabled',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'diff' => json_encode(['method' => 'totp']),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        // Show backup codes to the user once after enabling
        return view('auth.2fa.enabled', ['codes' => $codes]);
    }

    public function disable(Request $request)
    {
        $user = $request->user();
        TwoFactor::where('user_id', $user->id)->delete();

        try {
            DB::table('audit_logs')->insert([
                'actor_id' => $user->id,
                'user_id' => $user->id,
                'action' => '2fa_disabled',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'diff' => json_encode([]),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('user.settings')->with('success', 'Two-factor authentication disabled');
    }

    protected function generateBackupCodes()
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = bin2hex(random_bytes(4));
        }
        return $codes;
    }
}
