<?php

namespace App\Http\Controllers;

use App\Models\TwoFactor;
use App\Services\TOTP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

        $codesPlain = $this->generateBackupCodes();

        // Hash backup codes before storing
        $codesHashed = array_map(function($c) {
            return Hash::make($c);
        }, $codesPlain);

        $two = TwoFactor::updateOrCreate(
            ['user_id' => $user->id, 'method' => 'totp'],
            ['secret_enc' => $enc, 'enabled_at' => now(), 'backup_codes' => $codesHashed, 'backup_shown_at' => now()]
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

        // Redirect to settings and show backup codes once after enabling.
        return redirect()->route($this->getSettingsRoute())
            ->with('success', 'احراز هویت دو مرحله‌ای با موفقیت فعال شد')
            ->with('backup_codes', $codesPlain);
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

        return redirect()->route($this->getSettingsRoute())->with('success', 'Two-factor authentication disabled');
    }

    protected function getSettingsRoute(): string
    {
        return auth()->user()->isMerchant() ? 'merchant.settings' : 'user.settings';
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
