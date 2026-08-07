<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\BalanceSyncService;
use Illuminate\Support\Facades\Cache;

class WalletApiController extends Controller
{
    protected BalanceSyncService $balanceService;

    public function __construct(BalanceSyncService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function balance($id)
    {
        $wallet = Wallet::findOrFail($id);

        $cacheKey = $this->balanceService->cacheKey($wallet->id);

        // Use calculated balances (do not rely on potentially stale wallet->balance)
        $data = Cache::remember($cacheKey, 10, function () use ($wallet) {
            return $this->balanceService->calculateWalletBalance($wallet);
        });

        return response()->json([
            'balance' => $data['balance'] ?? '0',
            'confirmed_balance' => $data['confirmed'] ?? '0',
            'pending_balance' => $data['pending'] ?? '0',
            'last_block' => $wallet->last_scanned_block
        ]);

    }
}
