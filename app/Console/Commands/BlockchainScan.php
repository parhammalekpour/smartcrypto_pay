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
    protected $signature = 'blockchain:scan {--limit=20 : Number of transactions per wallet to inspect} {--wallet= : Optional wallet id to scan only} {--from-block= : Optional start block for a targeted historical scan} {--to-block= : Optional end block for a targeted historical scan}';

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
        $fromBlockOption = $this->option('from-block');
        $toBlockOption = $this->option('to-block');
        $targetedRange = $fromBlockOption !== null || $toBlockOption !== null;

        if ($targetedRange) {
            if ($fromBlockOption === null || $toBlockOption === null) {
                $this->error('Targeted scan requires both --from-block and --to-block.');
                return 1;
            }

            if (!is_numeric($fromBlockOption) || !is_numeric($toBlockOption)) {
                $this->error('Targeted scan requires numeric block values.');
                return 1;
            }

            $fromBlock = (int) $fromBlockOption;
            $toBlock = (int) $toBlockOption;

            if ($fromBlock < 0 || $toBlock < 0) {
                $this->error('Block numbers must be >= 0.');
                return 1;
            }

            if ($fromBlock > $toBlock) {
                $this->error('The --from-block value must be less than or equal to --to-block.');
                return 1;
            }

            $this->info('Targeted scan: Wallet: ' . ($walletId ?? 'all') . ' Range: ' . $fromBlock . ' -> ' . $toBlock);
        } else {
            $fromBlock = null;
            $toBlock = null;
            $this->info('Starting blockchain scan (limit per wallet: ' . $limit . ')' . ($walletId ? ' for wallet ' . $walletId : ''));
        }

        try {
            $summary = $this->scanner->scanOnce($limit, $walletId, $fromBlock, $toBlock);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        } catch (\Throwable $e) {
            $this->error('Blockchain scan failed: ' . $e->getMessage());
            return 1;
        }

        if ($targetedRange) {
            $this->info('Final summary: Created: ' . $summary['created'] . ', Skipped: ' . $summary['skipped'] . ', Errors: ' . count($summary['errors'] ?? []));
        } else {
            $this->info('Scan complete. Created: ' . $summary['created'] . ', Skipped: ' . $summary['skipped'] . ($walletId ? ' for wallet ' . $walletId : ''));
        }

        if (!empty($summary['errors'])) {
            $this->error('Errors:');
            foreach ($summary['errors'] as $err) {
                $this->line($err);
            }
        }

        return 0;
    }
}
