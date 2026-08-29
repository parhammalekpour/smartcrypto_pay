<?php

namespace App\Http\Controllers;

use App\Models\Wallet;

class WalletApiController extends Controller
{
    public function balance($id)
    {
        $wallet = Wallet::findOrFail($id);
        $balances = app(\App\Services\BalanceSyncService::class)->calculateWalletBalance($wallet);

        $availableBalance = (string) ($balances['balance'] ?? ($wallet->balance ?? '0'));
        $confirmedBalance = (string) ($balances['confirmed'] ?? ($wallet->onchain_balance ?? ($wallet->balance ?? '0')));

        // Expose the confirmed on-chain balance as the main displayed balance so UI shows the authoritative value.
        return response()->json([
            // Primary display balance: confirmed on-chain value
            'balance' => $confirmedBalance,
            'wallet_balance' => $confirmedBalance,
            // Keep available_balance for spendable/derived value (on-chain minus active withdrawals)
            'available_balance' => $availableBalance,
            'confirmed' => $confirmedBalance,
            'confirmed_balance' => $confirmedBalance,
            'pending_balance' => (string) ($balances['pending'] ?? '0'),
            'last_block' => $wallet->last_scanned_block
        ]);
    }
}
