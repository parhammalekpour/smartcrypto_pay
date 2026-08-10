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

    protected function rpcHostForNetwork(string $network): string
    {
        if ($network === 'sepolia') {
            return 'eth-sepolia.g.alchemy.com';
        }

        return 'eth-' . $network . '.g.alchemy.com';
    }

    protected function buildNodeEnvironment(array $overrides = []): array
    {
        $baseEnv = [];

        if (is_array($_ENV)) {
            $baseEnv = array_merge($baseEnv, $_ENV);
        }
        if (is_array($_SERVER)) {
            $baseEnv = array_merge($baseEnv, $_SERVER);
        }

        $currentEnv = getenv();
        if (is_array($currentEnv)) {
            $baseEnv = array_merge($baseEnv, $currentEnv);
        }

        foreach (['PATH', 'HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY', 'NO_PROXY', 'http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'] as $name) {
            $value = getenv($name);
            if ($value !== false) {
                $baseEnv[$name] = $value;
            }
        }

        $env = [];
        foreach ($baseEnv as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_string($value) || is_numeric($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $env[$key] = (string)$value;
            }
        }

        return array_merge($env, $overrides);
    }

    protected function detectNodeExecutablePath(): string
    {
        $finderCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? ['where', 'node'] : ['which', 'node'];
        $finderProcess = new Process($finderCmd, base_path(), null);
        $finderProcess->setTimeout(10);

        try {
            $finderProcess->run();
            if ($finderProcess->isSuccessful()) {
                $output = trim($finderProcess->getOutput());
                $firstLine = preg_split('/\r?\n/', $output)[0] ?? 'node';
                return $firstLine;
            }
        } catch (\Throwable $_) {
        }

        return 'node';
    }

    protected function runNode(array $args, ?string $privateKey = null): array
    {
        // Read API key only from Laravel .env via env()
        $apiKey = env('ALCHEMY_API_KEY');
        if (empty($apiKey)) {
            throw new \RuntimeException('ALCHEMY_API_KEY not configured in .env');
        }

        $overrides = ['ALCHEMY_API_KEY' => $apiKey];
        if ($privateKey !== null && $privateKey !== '') {
            $overrides['PRIVATE_KEY'] = $privateKey;
        }

        $env = $this->buildNodeEnvironment($overrides);
        $cmd = array_merge(['node', $this->scriptPath], $args, ['--network', $this->network]);

        Log::info('EthereumService running node process', [
            'node_executable_path' => $this->detectNodeExecutablePath(),
            'cwd' => base_path(),
            'network' => $this->network,
            'rpcHost' => $this->rpcHostForNetwork($this->network),
            'hasAlchemyApiKey' => !empty($apiKey),
            'hasPrivateKey' => isset($overrides['PRIVATE_KEY']) && $overrides['PRIVATE_KEY'] !== '',
            'PATH_exists' => array_key_exists('PATH', $env),
            'HTTPS_PROXY_exists' => array_key_exists('HTTPS_PROXY', $env) || array_key_exists('https_proxy', $env),
            'HTTP_PROXY_exists' => array_key_exists('HTTP_PROXY', $env) || array_key_exists('http_proxy', $env),
            'ALL_PROXY_exists' => array_key_exists('ALL_PROXY', $env) || array_key_exists('all_proxy', $env),
            'NO_PROXY_exists' => array_key_exists('NO_PROXY', $env) || array_key_exists('no_proxy', $env),
        ]);

        $process = new Process($cmd, base_path(), $env);
        $process->setTimeout(60);

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
            throw new \RuntimeException('Ethereum client error: ' . (is_string($data['error']) ? $data['error'] : json_encode($data['error'])));
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
        $res = $this->runNode(['isAddress', $address]);
        return isset($res['result']) ? (bool)$res['result'] : false;
    }

    /**
     * Get ETH balance (formatted in ether) for an address
     * @param string $address
     * @return string  balance in ETH (e.g. "0.1234")
     */
    public function getBalance(string $address): string
    {
        $res = $this->runNode(['balance', $address]);
        return $res['balance'] ?? '0';
    }

    /**
     * Parse a user-provided ETH amount string to Wei via ethers.js.
     */
    public function parseEther(string $amount): string
    {
        $res = $this->runNode(['parseEther', $amount]);
        return $res['wei'] ?? '0';
    }

    /**
     * Derive the signer's address from a provided private key without logging the key.
     */
    public function getSignerAddress(string $privateKey): string
    {
        $res = $this->runNode(['signerAddress'], $privateKey);
        return $res['signerAddress'] ?? '';
    }

    /**
     * Estimate gas usage for a simple ETH send to the requested receiver.
     */
    public function estimateGas(string $fromAddress, string $toAddress, string $amountEth): array
    {
        $res = $this->runNode(['diagnose', $fromAddress, $toAddress, $amountEth]);

        if (isset($res['estimate']) && is_array($res['estimate'])) {
            $gasLimit = $res['estimate']['gasLimit'] ?? null;
            $gasPrice = $res['gasPrice'] ?? null;
            $gasCostWei = null;
            $gasCostEth = null;

            if ($gasLimit !== null && $gasPrice !== null) {
                try {
                    $gasCostWei = bcmul((string)$gasLimit, (string)$gasPrice);
                    $gasCostEth = bcdiv((string)$gasCostWei, '1000000000000000000', 18);
                } catch (\Throwable $_) {
                    $gasCostWei = null;
                    $gasCostEth = null;
                }
            }

            return array_merge($res, [
                'gasCostWei' => $gasCostWei,
                'gasCostEth' => $gasCostEth
            ]);
        }

        if (isset($res['estimateError'])) {
            throw new \RuntimeException(json_encode($res));
        }

        return $res;
    }

    /**
     * Broadcast an ETH transaction signed by the wallet's private key.
     */
    public function sendTransaction(string $privateKey, string $toAddress, string $amountEth): array
    {
        $res = $this->runNode(['send', $toAddress, $amountEth], $privateKey);
        return $res;
    }

    /**
     * Fetch a transaction receipt and confirmations from the Sepolia RPC.
     */
    public function getTransactionReceipt(string $txHash): array
    {
        $res = $this->runNode(['receipt', $txHash]);
        return $res;
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

        $res = $this->runNode($args);
        return $res['transfers'] ?? [];
    }

    /**
     * Get current block number from provider
     * @return int
     */
    public function getCurrentBlockNumber(): int
    {
        $res = $this->runNode(['blockNumber']);
        return isset($res['blockNumber']) ? (int)$res['blockNumber'] : 0;
    }
}

