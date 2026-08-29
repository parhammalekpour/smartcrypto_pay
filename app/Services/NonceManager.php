<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletNonce;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NonceManager
{
    protected EthereumService $ethService;

    public function __construct(?EthereumService $ethService = null)
    {
        $this->ethService = $ethService ?? new EthereumService();
    }

    /**
     * Reserve a nonce for the given wallet in a transaction-safe way.
     * Returns the reserved nonce (integer).
     */
    public function reserveNonceForWallet(Wallet $wallet): int
    {
        $address = trim((string) ($wallet->wallet_address ?? ''));
        if ($address === '') {
            throw new \InvalidArgumentException('Wallet address is empty for wallet id ' . ($wallet->id ?? '?'));
        }

        return DB::transaction(function () use ($wallet, $address) {
            // Obtain (or create) a wallet_nonces row using SELECT ... FOR UPDATE semantics.
            $row = WalletNonce::where('wallet_id', $wallet->id)->lockForUpdate()->first();

            if ($row === null) {
                // First-time init: reconcile with chain's pending nonce
                try {
                    $chainNonce = $this->ethService->getTransactionCount($address, 'pending');
                    if (!is_numeric($chainNonce)) {
                        Log::warning('NonceManager: non-numeric chain nonce returned, defaulting to 0', ['wallet_id' => $wallet->id, 'chainNonce' => $chainNonce]);
                        $chainNonce = 0;
                    }
                    $chainNonce = (int) $chainNonce;
                } catch (\Throwable $e) {
                    Log::error('NonceManager: failed to get chain nonce: ' . $e->getMessage(), ['wallet_id' => $wallet->id]);
                    // conservative: fail the reservation so caller can decide; do not silently pick a nonce
                    throw $e;
                }

                // Reserve chainNonce as current reserved nonce, store next_nonce = chainNonce + 1
                $reserved = $chainNonce;
                $row = WalletNonce::create([
                    'wallet_id' => $wallet->id,
                    'address' => strtolower($address),
                    'next_nonce' => $chainNonce + 1,
                    'locked_at' => now(),
                ]);

                Log::info('NonceManager: initialized wallet_nonces row', ['wallet_id' => $wallet->id, 'reserved' => $reserved, 'next_nonce' => $row->next_nonce]);
                return $reserved;
            }

            // Reconcile chain nonce vs stored next_nonce
            try {
                $chainNonce = $this->ethService->getTransactionCount($address, 'pending');
                $chainNonce = is_numeric($chainNonce) ? (int)$chainNonce : null;
            } catch (\Throwable $e) {
                Log::warning('NonceManager: getTransactionCount failed during reconcile, will use stored next_nonce', ['wallet_id' => $wallet->id, 'error' => $e->getMessage()]);
                $chainNonce = null;
            }

            $storedNext = $row->next_nonce;

            if ($chainNonce !== null && $storedNext !== null && $storedNext < $chainNonce) {
                // Our DB is behind chain — move forward to chain
                $reserved = $chainNonce;
                $row->next_nonce = $chainNonce + 1;
                $row->locked_at = now();
                $row->address = strtolower($address);
                $row->save();

                Log::info('NonceManager: reconciled stored next_nonce behind chain; advanced to chain nonce', ['wallet_id' => $wallet->id, 'chainNonce' => $chainNonce, 'reserved' => $reserved]);
                return $reserved;
            }

            if ($chainNonce !== null && ($storedNext === null) ) {
                // stored missing but chain available
                $reserved = $chainNonce;
                $row->next_nonce = $chainNonce + 1;
                $row->locked_at = now();
                $row->address = strtolower($address);
                $row->save();
                Log::info('NonceManager: filled missing stored next_nonce from chain', ['wallet_id' => $wallet->id, 'reserved' => $reserved]);
                return $reserved;
            }

            if ($storedNext !== null) {
                // Normal case: use stored next_nonce
                $reserved = (int) $storedNext;
                $row->next_nonce = $reserved + 1;
                $row->locked_at = now();
                $row->address = strtolower($address);
                $row->save();

                Log::info('NonceManager: reserved stored next_nonce', ['wallet_id' => $wallet->id, 'reserved' => $reserved, 'next_nonce' => $row->next_nonce]);
                return $reserved;
            }

            // Fallback conservative behavior: attempt to get chain nonce again or fail
            if ($chainNonce !== null) {
                $reserved = (int)$chainNonce;
                $row->next_nonce = $chainNonce + 1;
                $row->locked_at = now();
                $row->address = strtolower($address);
                $row->save();
                Log::info('NonceManager: fallback used chain nonce', ['wallet_id' => $wallet->id, 'reserved' => $reserved]);
                return $reserved;
            }

            throw new \RuntimeException('NonceManager: unable to determine nonce for wallet ' . $wallet->id);
        }, 5); // retry up to 5 times on deadlock
    }
}
