<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\EthereumService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RecoverBroadcastingTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    protected int $limit;

    public function __construct(int $limit = 50)
    {
        $this->limit = $limit;
    }

    public function handle(EthereumService $ethService)
    {
        $delaySeconds = (int) config('ethereum.broadcast_recovery_delay', 60);
        $threshold = now()->subSeconds($delaySeconds);

        $candidates = Transaction::where('status', 'broadcasting')
            ->whereNull('tx_hash')
            ->where('last_checked_at', '<', $threshold)
            ->limit($this->limit)
            ->get();

        if ($candidates->isEmpty()) {
            Log::debug('RecoverBroadcastingTransactions: no candidates found');
            return;
        }

        foreach ($candidates as $tx) {
            try {
                // Require stored nonce for reliable correlation
                if (empty($tx->nonce) && $tx->nonce !== 0) {
                    Log::info('RecoverBroadcastingTransactions: skipping transaction with no nonce', ['transaction_id' => $tx->id]);
                    $tx->last_checked_at = now();
                    $tx->save();
                    continue;
                }

                $wallet = $tx->wallet;
                if (!$wallet) {
                    Log::warning('RecoverBroadcastingTransactions: transaction has no wallet', ['transaction_id' => $tx->id]);
                    $tx->last_checked_at = now();
                    $tx->save();
                    continue;
                }

                $from = strtolower(trim((string) ($tx->from_address ?? $tx->sender_wallet_address ?? $wallet->wallet_address ?? '')));
                $to = strtolower(trim((string) ($tx->to_address ?? $tx->receiver_wallet_address ?? '')));
                $nonce = (int) $tx->nonce;
                $currency = strtoupper((string) ($tx->currency ?? $wallet->currency ?? 'ETH'));

                if (empty($from) || empty($to)) {
                    Log::warning('RecoverBroadcastingTransactions: missing from/to for correlation', ['transaction_id' => $tx->id]);
                    $tx->last_checked_at = now();
                    $tx->save();
                    continue;
                }

                $foundHash = null;
                $onchainTx = null;

                if ($currency === 'USDT') {
                    $contract = config('ethereum.usdt_contract_address');
                    if (empty($contract)) {
                        Log::warning('RecoverBroadcastingTransactions: USDT contract not configured; skipping USDT recovery', ['transaction_id' => $tx->id]);
                        $tx->last_checked_at = now();
                        $tx->save();
                        continue;
                    }

                    // Fetch recent token transfers to the recipient and inspect candidates
                    $transfers = [];
                    try {
                        $transfers = $ethService->getTokenTransfers($contract, $to, 50, null);
                    } catch (\Throwable $e) {
                        Log::warning('RecoverBroadcastingTransactions: token transfer lookup failed', ['transaction_id' => $tx->id, 'error' => $e->getMessage()]);
                    }

                    foreach ($transfers as $t) {
                        $tHash = $t['hash'] ?? null;
                        $tFrom = strtolower(trim((string) ($t['from'] ?? '')));
                        $tValue = (string) ($t['value'] ?? '0');

                        if (empty($tHash) || $tFrom !== $from) {
                            continue;
                        }

                        // Compare amounts using BC Math with 6 decimals for USDT
                        if (bccomp((string)$tx->amount, (string)$tValue, 6) !== 0) {
                            continue;
                        }

                        // Fetch on-chain transaction details
                        try {
                            $lookup = $ethService->getTransactionByHash($tHash);
                        } catch (\Throwable $e) {
                            Log::warning('RecoverBroadcastingTransactions: getTransactionByHash failed', ['transaction_id' => $tx->id, 'hash' => $tHash, 'error' => $e->getMessage()]);
                            continue;
                        }

                        $candidate = $lookup['transaction'] ?? null;
                        if (empty($candidate)) continue;

                        $candNonce = isset($candidate['nonce']) ? (int)$candidate['nonce'] : null;
                        $candFrom = isset($candidate['from']) ? strtolower(trim((string)$candidate['from'])) : null;

                        if ($candNonce === $nonce && $candFrom === $from) {
                            $foundHash = $tHash;
                            $onchainTx = $candidate;
                            break;
                        }
                    }
                } else {
                    // ETH correlation: inspect recent transaction history for this sender
                    $history = [];
                    try {
                        $history = $ethService->getTransactionHistory($from, 50);
                    } catch (\Throwable $e) {
                        Log::warning('RecoverBroadcastingTransactions: history lookup failed', ['transaction_id' => $tx->id, 'error' => $e->getMessage()]);
                    }

                    foreach ($history as $h) {
                        $hHash = $h['hash'] ?? null;
                        if (empty($hHash)) continue;

                        try {
                            $lookup = $ethService->getTransactionByHash($hHash);
                        } catch (\Throwable $e) {
                            Log::warning('RecoverBroadcastingTransactions: getTransactionByHash failed', ['transaction_id' => $tx->id, 'hash' => $hHash, 'error' => $e->getMessage()]);
                            continue;
                        }

                        $candidate = $lookup['transaction'] ?? null;
                        if (empty($candidate)) continue;

                        $candNonce = isset($candidate['nonce']) ? (int)$candidate['nonce'] : null;
                        $candFrom = isset($candidate['from']) ? strtolower(trim((string)$candidate['from'])) : null;
                        $candTo = isset($candidate['to']) ? strtolower(trim((string)$candidate['to'])) : null;

                        if ($candNonce === $nonce && $candFrom === $from && $candTo === $to) {
                            // For ETH also check value equality using 18 decimals
                            $value = $h['value'] ?? null;
                            if ($value !== null && bccomp((string)$tx->amount, (string)$value, 18) !== 0) {
                                continue;
                            }

                            $foundHash = $hHash;
                            $onchainTx = $candidate;
                            break;
                        }
                    }
                }

                if ($foundHash === null) {
                    // No match found; update last_checked_at and continue
                    $tx->last_checked_at = now();
                    $tx->save();
                    Log::info('RecoverBroadcastingTransactions: no on-chain match found', ['transaction_id' => $tx->id]);
                    continue;
                }

                // Idempotent persist: short DB transaction and lock
                DB::transaction(function () use ($tx, $foundHash, $onchainTx) {
                    $locked = Transaction::whereKey($tx->id)->lockForUpdate()->first();
                    if (!$locked) return;

                    if (!empty($locked->tx_hash)) {
                        // Another worker recovered it
                        return;
                    }

                    if ($locked->status !== 'broadcasting') {
                        // Status changed in the meantime
                        return;
                    }

                    $update = [
                        'tx_hash' => $foundHash,
                        'status' => 'pending',
                        'broadcasted_at' => now(),
                        'last_checked_at' => now(),
                    ];

                    if (isset($onchainTx['blockNumber']) && $onchainTx['blockNumber'] !== null) {
                        $update['block_number'] = is_numeric($onchainTx['blockNumber']) ? (int)$onchainTx['blockNumber'] : (int)hexdec($onchainTx['blockNumber']);
                    }

                    $locked->update($update);
                    Log::info('RecoverBroadcastingTransactions: recovered tx_hash and persisted', ['transaction_id' => $tx->id, 'tx_hash' => $foundHash]);
                });

            } catch (\Throwable $e) {
                Log::error('RecoverBroadcastingTransactions: unexpected error while processing candidate', ['transaction_id' => $tx->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
