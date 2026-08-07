<?php

namespace App\Jobs;

use App\Services\BlockchainDepositService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BlockchainScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    protected int $limit;
    protected ?int $walletId;

    public function __construct(int $limit = 50, ?int $walletId = null)
    {
        $this->limit = $limit;
        $this->walletId = $walletId;
    }

    public function handle(BlockchainDepositService $scanner)
    {
        Log::info('Scheduled blockchain scan started', ['limit' => $this->limit, 'wallet' => $this->walletId]);

        try {
            $summary = $scanner->scanOnce($this->limit, $this->walletId);
            Log::info('Scheduled blockchain scan finished', $summary);
        } catch (\Throwable $e) {
            Log::error('Scheduled blockchain scan failed: ' . $e->getMessage(), ['exception' => $e]);
            // rethrow to respect job retry semantics
            throw $e;
        }
    }
}
