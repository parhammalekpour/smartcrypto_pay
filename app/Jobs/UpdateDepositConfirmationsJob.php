<?php

namespace App\Jobs;

use App\Models\Deposit;
use App\Models\Wallet;
use App\Services\BalanceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\EthereumService;
use Illuminate\Support\Facades\DB;

class UpdateDepositConfirmationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct()
    {
    }

    public function handle(EthereumService $ethService, BalanceSyncService $balanceService)
    {
        Log::info('Confirmation updater job started');

        try {
            $currentBlock = null;
            try {
                $currentBlock = $ethService->getCurrentBlockNumber();
            } catch (\Throwable $e) {
                Log::error('Failed to get current block number for confirmations update: ' . $e->getMessage());
            }

            // Find pending deposits which have a block_number
            $pending = Deposit::where('status', 'pending')
                ->whereNotNull('block_number')
                ->get();

            foreach ($pending as $deposit) {
                try {
                    if ($currentBlock === null || $deposit->block_number === null) {
                        // can't compute confirmations
                        continue;
                    }

                    $newConfirmations = max(0, $currentBlock - (int)$deposit->block_number + 1);

                    if ($deposit->confirmations !== $newConfirmations) {
                        DB::transaction(function () use ($deposit, $newConfirmations, $balanceService) {
                            $deposit->confirmations = $newConfirmations;

                            $wasPending = ($deposit->status === 'pending');
                            if ($newConfirmations >= 12) {
                                $deposit->status = 'confirmed';
                                Log::info('Deposit confirmed', ['deposit_id' => $deposit->id, 'tx_hash' => $deposit->tx_hash, 'wallet_id' => $deposit->wallet_id]);
                            }

                            $deposit->save();

                            // If it became confirmed, trigger balance sync for its wallet
                            if ($deposit->status === 'confirmed') {
                                $wallet = Wallet::find($deposit->wallet_id);
                                if ($wallet) {
                                    try {
                                        $balanceService->syncWallet($wallet);
                                        Log::info('Wallet balance updated after confirmation', ['wallet_id' => $wallet->id, 'balance' => $wallet->balance]);
                                    } catch (\Throwable $e) {
                                        Log::error('Balance sync failed for wallet ' . $deposit->wallet_id . ': ' . $e->getMessage());
                                    }
                                }
                            }
                        });
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed while updating confirmations for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage());
                }
            }

            Log::info('Confirmation updater job finished', ['checked' => $pending->count()]);
        } catch (\Throwable $e) {
            Log::error('Confirmation updater job failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
