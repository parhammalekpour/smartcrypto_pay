<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BlockchainDepositService;

class BlockchainScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blockchain:scan {--limit=20 : Number of transactions per wallet to inspect} {--wallet= : Optional wallet id to scan only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan blockchain for incoming deposits to platform wallets';

    protected BlockchainDepositService $scanner;

    public function __construct(BlockchainDepositService $scanner)
    {
        parent::__construct();

        $this->scanner = $scanner;
    }

    public function handle()
    {
        $limit = (int)$this->option('limit');
        $walletId = $this->option('wallet') !== null ? (int)$this->option('wallet') : null;

        $this->info('Starting blockchain scan (limit per wallet: ' . $limit . ')' . ($walletId ? ' for wallet ' . $walletId : ''));

        $summary = $this->scanner->scanOnce($limit, $walletId);

        $this->info('Scan complete. Created: ' . $summary['created'] . ', Skipped: ' . $summary['skipped'] . ($walletId ? ' for wallet ' . $walletId : ''));

        if (!empty($summary['errors'])) {
            $this->error('Errors:');
            foreach ($summary['errors'] as $err) {
                $this->line($err);
            }
        }

        return 0;
    }
}
