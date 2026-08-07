<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BlockchainDepositService;

class BlockchainProcessDeposits extends Command
{
    protected $signature = 'blockchain:process-deposits';

    protected $description = 'Process confirmed blockchain deposits and apply them to internal wallet balances';

    protected BlockchainDepositService $depositService;

    public function __construct(BlockchainDepositService $depositService)
    {
        parent::__construct();

        $this->depositService = $depositService;
    }

    public function handle(): int
    {
        $this->info('Processing confirmed deposits...');

        $summary = $this->depositService->processPendingConfirmedDeposits();

        $this->info('Processed: ' . $summary['processed'] . ', Skipped: ' . $summary['skipped']);

        if (!empty($summary['errors'])) {
            $this->error('Errors:');
            foreach ($summary['errors'] as $error) {
                $this->line($error);
            }
        }

        return 0;
    }
}
