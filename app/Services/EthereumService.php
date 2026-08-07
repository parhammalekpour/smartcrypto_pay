<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class EthereumService
{
    protected string $scriptPath;
    protected string $network = 'sepolia';

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/ethereum-client.js');
        $this->network = env('ETHEREUM_NETWORK', 'sepolia');
    }

    protected function runNode(array $args): array
    {
        // Read API key only from Laravel .env via env()
        $apiKey = env('ALCHEMY_API_KEY');
        if (empty($apiKey)) {
            throw new \RuntimeException('ALCHEMY_API_KEY not configured in .env');
        }

        $cmd = array_merge(['node', $this->scriptPath], $args);

        $process = new Process($cmd, base_path(), ['ALCHEMY_API_KEY' => $apiKey]);
        $process->setTimeout(20);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::error('EthereumService node execution failed: ' . $e->getMessage());
            throw new \RuntimeException('Ethereum node execution failed');
        }

        $stdout = trim($process->getOutput());
        $stderr = trim($process->getErrorOutput());

        $payload = $stdout ?: $stderr;

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            Log::error('Invalid JSON from ethereum-client.js: ' . $payload);
            throw new \RuntimeException('Invalid response from ethereum client');
        }

        if (isset($data['error'])) {
            throw new \RuntimeException('Ethereum client error: ' . $data['error']);
        }

        return $data;
    }

    /**
     * Check if an address is valid
     * @param string $address
     * @return bool
     */
    public function isValidAddress(string $address): bool
    {
        $res = $this->runNode(['isAddress', $address, $this->network]);
        return isset($res['result']) ? (bool)$res['result'] : false;
    }

    /**
     * Get ETH balance (formatted in ether) for an address
     * @param string $address
     * @return string  balance in ETH (e.g. "0.1234")
     */
    public function getBalance(string $address): string
    {
        $res = $this->runNode(['balance', $address, $this->network]);
        return $res['balance'] ?? '0';
    }

    /**
     * Get latest transactions for an address (read-only). Returns array of tx summaries.
     * @param string $address
     * @param int $limit
     * @return array
     */
    public function getTransactionHistory(string $address, int $limit = 10, $fromBlock = null): array
    {
        $args = ['history', $address, (string)$limit];
        if ($fromBlock !== null) {
            $args[] = (string)$fromBlock;
        }
        $args[] = $this->network;

        $res = $this->runNode($args);

        // Support different response shapes (transactions, transfers)
        $items = $res['transactions'] ?? $res['transfers'] ?? [];

        // Normalize items: ensure keys hash, from, to, value, blockNumber exist
        $normalized = [];
        foreach ($items as $it) {
            $blockNumber = null;
            if (isset($it['blockNumber']) && is_numeric($it['blockNumber'])) {
                $blockNumber = (int)$it['blockNumber'];
            } elseif (isset($it['blockNum'])) {
                // blockNum may be hex string like '0xA'
                if (is_string($it['blockNum']) && str_starts_with($it['blockNum'], '0x')) {
                    $blockNumber = hexdec($it['blockNum']);
                } else {
                    $blockNumber = (int)$it['blockNum'];
                }
            }

            $value = $it['value'] ?? ($it['valueRaw'] ?? null);

            $normalized[] = [
                'hash' => $it['hash'] ?? ($it['transactionHash'] ?? null),
                'from' => $it['from'] ?? null,
                'to' => $it['to'] ?? null,
                'value' => $value,
                'blockNumber' => $blockNumber,
            ];
        }

        return $normalized;
    }

    /**
     * Get token transfers (ERC-20 Transfer events) to a specific address
     * @param string $contractAddress
     * @param string $toAddress
     * @param int $limit
     * @param int|null $fromBlock
     * @return array
     */
    public function getTokenTransfers(string $contractAddress, string $toAddress, int $limit = 10, $fromBlock = null): array
    {
        $args = ['tokenTransfers', $contractAddress, $toAddress, (string)$limit];
        if ($fromBlock !== null) {
            $args[] = (string)$fromBlock;
        }
        $args[] = $this->network;

        $res = $this->runNode($args);
        return $res['transfers'] ?? [];
    }

    /**
     * Get current block number from provider
     * @return int
     */
    public function getCurrentBlockNumber(): int
    {
        $res = $this->runNode(['blockNumber', $this->network]);
        return isset($res['blockNumber']) ? (int)$res['blockNumber'] : 0;
    }
}

