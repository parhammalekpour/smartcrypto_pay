<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BlockchainWalletService
{
    protected string $nodeScriptPath;

    public function __construct()
    {
        // Path to the Node script that generates HD wallets
        $this->nodeScriptPath = base_path('scripts/hd-wallet-generate.js');
    }

    /**
     * Generate an HD wallet using ethers.js Node script.
     * Returns array with keys: address, privateKey
     * Throws \RuntimeException on failure.
     */
    public function generateHdWallet(string $currency = 'ETH') : array
    {
        // Use node to execute the script and get JSON output
        $process = new Process(['node', $this->nodeScriptPath, $currency]);
        // Give it a short timeout
        $process->setTimeout(15);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::error('HD wallet process execution failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to generate wallet');
        }

        if (!$process->isSuccessful()) {
            // Attempt to decode stderr JSON
            $stderr = $process->getErrorOutput();
            $message = $stderr ?: $process->getOutput();
            Log::error('HD wallet script failed: ' . $message);
            throw new \RuntimeException('HD wallet generation failed');
        }

        $output = $process->getOutput();
        $data = json_decode($output, true);

        if (!is_array($data) || empty($data['address']) || empty($data['privateKey'])) {
            Log::error('Invalid HD wallet script output: ' . $output);
            throw new \RuntimeException('Invalid HD wallet generation output');
        }

        return [
            'address' => $data['address'],
            'privateKey' => $data['privateKey']
        ];
    }
}
