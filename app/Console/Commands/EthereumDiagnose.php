<?php

namespace App\Console\Commands;

use App\Services\EthereumService;
use Illuminate\Console\Command;

class EthereumDiagnose extends Command
{
    protected $signature = 'ethereum:diagnose';

    protected $description = 'Diagnose the configured Sepolia Ethereum provider and report chain validation status without exposing secrets.';

    public function handle(EthereumService $ethereum): int
    {
        try {
            $diagnostic = $ethereum->diagnoseProvider();
        } catch (\Throwable $e) {
            $this->error('Ethereum RPC connection failed for Sepolia.');
            $this->error('Expected chainId: 11155111');
            $this->error('Reason: ' . $e->getMessage());
            return 1;
        }

        $this->info('Configured network: ' . ($diagnostic['configuredNetwork'] ?? 'unknown'));
        $this->info('Expected chain ID: ' . ($diagnostic['expectedChainId'] ?? 'unknown'));
        $this->info('Detected chain ID: ' . ($diagnostic['detectedChainId'] ?? 'unknown'));
        $this->info('Detected network: ' . ($diagnostic['detectedNetwork'] ?? 'unknown'));
        $this->info('RPC host: ' . ($diagnostic['rpcHost'] ?? 'unknown'));
        $this->info('Latest block: ' . ($diagnostic['latestBlock'] ?? 'unknown'));
        $this->info('Provider: ' . (($diagnostic['provider'] ?? 'UNKNOWN') === 'OK' ? 'OK' : 'ERROR'));
        $this->info('USDT contract: ' . ($diagnostic['usdtContract'] ?? 'not configured'));
        $this->info('USDT contract decimals: ' . ($diagnostic['usdtDecimals'] ?? 'unknown'));

        if (($diagnostic['provider'] ?? 'UNKNOWN') !== 'OK') {
            $this->error('Provider status: ' . ($diagnostic['provider'] ?? 'UNKNOWN'));
            return 1;
        }

        return 0;
    }
}
