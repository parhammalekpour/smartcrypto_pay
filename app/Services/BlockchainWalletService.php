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
    protected function runNodeScript(array $args): array
    {
        $process = new Process(array_merge(['node', $this->nodeScriptPath], $args));
        $process->setTimeout(15);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::error('HD wallet process execution failed: ' . $e->getMessage(), ['args' => $args]);
            throw new \RuntimeException('Failed to execute HD wallet helper');
        }

        if (!$process->isSuccessful()) {
            $stderr = $process->getErrorOutput();
            $message = trim($stderr ?: $process->getOutput());
            Log::error('HD wallet script failed: ' . $message, ['args' => $args]);
            throw new \RuntimeException('HD wallet generation failed');
        }

        $output = $process->getOutput();
        $data = json_decode($output, true);
        if (!is_array($data)) {
            Log::error('Invalid HD wallet script output: ' . $output, ['args' => $args]);
            throw new \RuntimeException('Invalid HD wallet generation output');
        }

        return $data;
    }

    public function generateHdWallet(string $currency = 'ETH') : array
    {
        $data = $this->runNodeScript([$currency]);

        $address = $data['address'] ?? null;
        $privateKey = $data['privateKey'] ?? null;

        if (empty($address) || empty($privateKey)) {
            Log::error('Invalid HD wallet script output: ' . json_encode($data));
            throw new \RuntimeException('Invalid HD wallet generation output');
        }

        if ($currency === 'ETH') {
            if (!$this->isValidAddress($address, $currency)) {
                Log::error('Generated address failed validation: ' . $address);
                throw new \RuntimeException('Generated address is invalid');
            }

            if (!preg_match('/^(0x)?[0-9a-fA-F]{64}$/', trim($privateKey))) {
                Log::error('Generated private key format is invalid', ['privateKeyLength' => strlen($privateKey)]);
                throw new \RuntimeException('Generated private key is invalid');
            }

            $derivedAddress = $this->deriveAddress($privateKey, $currency);
            if (strtolower($derivedAddress) !== strtolower($address)) {
                Log::error('Generated private key does not derive generated address', ['generatedAddress' => $address, 'derivedAddress' => $derivedAddress]);
                throw new \RuntimeException('Generated private key does not derive the generated wallet address');
            }
        }

        return [
            'address' => $address,
            'privateKey' => $privateKey
        ];
    }

    /**
     * Validate an address using the Node/ethers.js script.
     */
    public function isValidAddress(string $address, string $currency = 'ETH'): bool
    {
        $data = $this->runNodeScript(['validate', $currency, $address]);
        return isset($data['valid']) ? (bool)$data['valid'] : false;
    }

    /**
     * Derive the address from a private key. This is used to validate that the
     * private key belongs to the address stored on the wallet.
     */
    public function deriveAddress(string $privateKey, string $currency = 'ETH'): string
    {
        $data = $this->runNodeScript(['derive', $currency, $privateKey]);
        return $data['address'] ?? '';
    }
}
