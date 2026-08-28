<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class EthereumService
{
    protected string $scriptPath;
    protected string $network = 'sepolia';
    protected ?int $explicitNonce = null;

    // Simple in-process RPC response cache to avoid repeated node process spawns
    // for frequent read-only calls within the same job run. Cached only for
    // non-mutating operations (receipts, blocks, tx lookups, blockNumber, etc.).
    protected array $rpcCache = [];
    protected int $rpcCacheMax = 2000;

    // Instrumentation counters for diagnostics (static to aggregate across instances)
    protected static int $rpcCalls = 0;
    protected static int $rpcCacheHits = 0;
    protected static array $rpcOps = [];
    // Batch-related metrics
    protected static int $batchesSent = 0;
    protected static int $batchFailures = 0;

    /**
     * Return per-instance RPC/cache stats using the singleton instance's cache size.
     */
    public function getRpcStats(): array
    {
        return [
            'rpcCalls' => self::$rpcCalls,
            'rpcCacheHits' => self::$rpcCacheHits,
            'rpcOps' => self::$rpcOps,
            'rpcCacheSize' => count($this->rpcCache),
            'rpcCacheMax' => $this->rpcCacheMax,
            'batchesSent' => self::$batchesSent,
            'batchFailures' => self::$batchFailures,
        ];
    }

    public static function normalizeHumanAmountInput(?string $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        $normalized = trim((string) $amount);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(["\xC2\xA0", "\xA0", ' ', "\t", "\r", "\n"], '', $normalized);

        if (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)[eE][+-]?\d+$/', $normalized) === 1) {
            $scientific = self::scientificToDecimalString($normalized);
            if ($scientific !== null) {
                $normalized = $scientific;
            }
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (preg_match('/^[+-]?\d+(?:\.\d+)?$/', $normalized) !== 1) {
            return null;
        }

        if ($normalized[0] === '+') {
            $normalized = substr($normalized, 1);
        }

        if ($normalized === '0' || $normalized === '0.0' || $normalized === '0.00' || $normalized === '0.') {
            return '0';
        }

        return $normalized;
    }

    private static function scientificToDecimalString(string $value): ?string
    {
        if (!preg_match('/^([+-]?)(?:(\d+)(?:\.(\d*))?|\.(\d+))([eE]([+-]?\d+))?$/', trim($value), $matches)) {
            return null;
        }

        $sign = $matches[1] === '-' ? '-' : '';
        $intPart = $matches[2] ?? '';
        $fracPart = $matches[3] ?? ($matches[4] ?? '');
        $exp = isset($matches[5]) ? (int) $matches[6] : 0;

        $digits = ($intPart !== '' ? $intPart : '0') . $fracPart;
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return '0';
        }

        $decimalPos = strlen($intPart) + $exp;
        if ($decimalPos <= 0) {
            return $sign . '0.' . str_repeat('0', -$decimalPos) . $digits;
        }

        if ($decimalPos >= strlen($digits)) {
            return $sign . $digits . str_repeat('0', $decimalPos - strlen($digits));
        }

        return $sign . substr($digits, 0, $decimalPos) . '.' . substr($digits, $decimalPos);
    }

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/ethereum-client.js');
        $this->network = env('ETHEREUM_NETWORK', 'sepolia');
    }

    public function setExplicitNonce(?int $nonce): void
    {
        $this->explicitNonce = $nonce;
    }

    public function clearExplicitNonce(): void
    {
        $this->explicitNonce = null;
    }

    public function resolveNonce(?int $nonce): ?int
    {
        if ($nonce !== null) {
            return $nonce;
        }

        return $this->explicitNonce;
    }

    public function getExpectedChainId(): int
    {
        return 11155111;
    }

    public function resolveNetworkName(?string $network = null): string
    {
        $network = strtolower(trim((string) ($network ?? env('ETHEREUM_NETWORK', 'sepolia'))));
        return $network !== '' ? $network : 'sepolia';
    }

    public function resolveRpcUrl(?string $network = null): string
    {
        $network = $this->resolveNetworkName($network);

        $explicit = trim((string) (env('ETHEREUM_RPC_URL') ?: ''));
        if ($explicit !== '') {
            $parsed = parse_url($explicit);
            if ($parsed === false || empty($parsed['scheme']) || empty($parsed['host'])) {
                throw new \RuntimeException('ETHEREUM_RPC_URL is configured but malformed. Expected a full http(s) URL.');
            }

            return $explicit;
        }

        $apiKey = trim((string) env('ALCHEMY_API_KEY', ''));
        if ($apiKey === '') {
            throw new \RuntimeException('ALCHEMY_API_KEY not configured in .env');
        }

        return 'https://eth-' . $network . '.g.alchemy.com/v2/' . $apiKey;
    }

    protected function rpcHostForNetwork(string $network): string
    {
        $normalized = strtolower(trim($network));
        if ($normalized === '') {
            return 'eth-sepolia.g.alchemy.com';
        }

        if ($normalized === 'sepolia') {
            return 'eth-sepolia.g.alchemy.com';
        }

        return 'eth-' . $normalized . '.g.alchemy.com';
    }

    protected function buildNodeEnvironment(array $overrides = []): array
    {
        $whitelist = [
            'ALCHEMY_API_KEY',
            'ETHEREUM_NETWORK',
            'ETHEREUM_RPC_URL',
            'PATH', 'Path',
            'HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY', 'NO_PROXY',
            'http_proxy', 'https_proxy', 'all_proxy', 'no_proxy',
        ];

        $env = [];
        foreach ($whitelist as $name) {
            $val = getenv($name);
            if ($val === false || $val === null || $val === '') {
                $val = $_ENV[$name] ?? $_SERVER[$name] ?? null;
            }

            if ($val !== null && $val !== '' && $val !== false) {
                $env[$name] = (string)$val;
            }
        }

        $pathValue = getenv('PATH') ?: getenv('Path') ?: ($_ENV['PATH'] ?? $_SERVER['PATH'] ?? null);
        if (is_string($pathValue) && $pathValue !== '') {
            $env['PATH'] = $pathValue;
        }

        foreach ($overrides as $k => $v) {
            if (!is_string($k)) continue;
            if (is_string($v) || is_numeric($v) || (is_object($v) && method_exists($v, '__toString'))) {
                $env[$k] = (string)$v;
            }
        }

        return $env;
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
        \Log::error('NODE ENV CHECK', [
            'php_pid' => getmypid(),
            'cwd' => getcwd(),
            'base_path' => base_path(),
            'alchemy' => env('ALCHEMY_API_KEY') ? 'SET' : 'NOT SET',
            'network' => env('ETHEREUM_NETWORK'),
            'rpc' => env('ETHEREUM_RPC_URL') ? 'SET' : 'NOT SET',
            'path' => getenv('PATH'),
        ]);

        $apiKey = trim((string) env('ALCHEMY_API_KEY', ''));
        if ($apiKey === '') {
            throw new \RuntimeException('ALCHEMY_API_KEY not configured in .env');
        }

        $rpcUrl = $this->resolveRpcUrl($this->network);
        $rpcHost = parse_url($rpcUrl, PHP_URL_HOST) ?: $this->rpcHostForNetwork($this->network);

        $overrides = [
            'ALCHEMY_API_KEY' => $apiKey,
            'ETHEREUM_NETWORK' => $this->network,
            'ETHEREUM_RPC_URL' => $rpcUrl,
        ];
        if ($privateKey !== null && $privateKey !== '') {
            $overrides['PRIVATE_KEY'] = $privateKey;
        }

        $env = $this->buildNodeEnvironment($overrides);
        $cmd = array_merge(['node', $this->scriptPath], $args, ['--network', $this->network]);

        // Lightweight in-process cache for read-only RPC operations within the same
        // PHP process (job run). Avoids repeatedly spawning a Node process for the
        // same arguments during a single job execution.
        $op = is_array($args) && count($args) > 0 ? (string)$args[0] : 'unknown';
        $cacheableOps = ['receipt', 'getTransactionByHash', 'block', 'blockNumber', 'getTransaction', 'getTransactionByHashWithMeta', 'balance', 'tokenBalance', 'feeData', 'diagnose', 'tokenTransfers', 'tokenTransfersByAddress'];
        $cacheable = in_array($op, $cacheableOps, true) && ($privateKey === null || $privateKey === '');
        $cacheKey = null;

        // instrumentation: increment rpc call counter and track cache hits
        self::$rpcCalls++;

        // per-operation counter
        if (!isset(self::$rpcOps[$op])) {
            self::$rpcOps[$op] = 0;
        }
        self::$rpcOps[$op]++;

        if ($cacheable) {
            $cacheKey = $op . '|' . md5(json_encode($args));
            if (isset($this->rpcCache[$cacheKey])) {
                self::$rpcCacheHits++;
                return $this->rpcCache[$cacheKey];
            }
        }

        Log::info('EthereumService PHP runtime before Process launch', [
            'php_pid' => getmypid(),
            'cwd' => getcwd() ?: null,
            'base_path' => base_path(),
            'app_environment' => app()->environment(),
            'env_alchemy_api_key' => env('ALCHEMY_API_KEY') !== null && env('ALCHEMY_API_KEY') !== '' ? 'SET' : 'NOT SET',
            'env_ethereum_network' => env('ETHEREUM_NETWORK'),
            'env_ethereum_rpc_url' => env('ETHEREUM_RPC_URL') !== null && env('ETHEREUM_RPC_URL') !== '' ? 'SET' : 'NOT SET',
            'config_ethereum_network' => config('ethereum.network'),
            'config_ethereum_rpc_url' => config('ethereum.rpc_url') !== null && config('ethereum.rpc_url') !== '' ? 'SET' : 'NOT SET',
            'config_ethereum_alchemy_api_key' => config('ethereum.alchemy_api_key') !== null && config('ethereum.alchemy_api_key') !== '' ? 'SET' : 'NOT SET',
            'rpc_hostname' => parse_url($rpcUrl, PHP_URL_HOST) ?: $this->rpcHostForNetwork($this->network),
            'env_final_keys' => array_keys($env),
            'env_final_status' => [
                'ALCHEMY_API_KEY' => array_key_exists('ALCHEMY_API_KEY', $env) ? 'SET' : 'NOT SET',
                'ETHEREUM_NETWORK' => array_key_exists('ETHEREUM_NETWORK', $env) ? (string)($env['ETHEREUM_NETWORK'] ?? '') : 'NOT SET',
                'ETHEREUM_RPC_URL' => array_key_exists('ETHEREUM_RPC_URL', $env) ? 'SET' : 'NOT SET',
                'PATH' => array_key_exists('PATH', $env) ? 'SET' : 'NOT SET',
            ],
            'path_set' => array_key_exists('PATH', $env) ? 'SET' : 'NOT SET',
            'command' => implode(' ', array_map(static fn ($part) => preg_match('/\s/', (string) $part) ? '"' . str_replace('"', '\\"', (string) $part) . '"' : (string) $part, $cmd)),
            'working_directory' => base_path(),
        ]);

        $process = new Process($cmd, base_path(), $env);
        $attempts = 2;
        $lastException = null;
        $processTimeoutSeconds = 120;
        $process->setTimeout($processTimeoutSeconds);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                Log::info('Running ethereum-client.js', ['attempt' => $attempt, 'cmd' => $cmd]);
                $process->run();

                $stdout = trim($process->getOutput());
                $stderr = trim($process->getErrorOutput());
                $payload = $stdout !== '' ? $stdout : $stderr;

                if ($payload === '') {
                    $lastException = new \RuntimeException('Empty response from ethereum client (stdout+stderr empty)');
                    Log::warning('Empty response from ethereum-client.js, will retry if attempts remain', ['attempt' => $attempt]);
                    if ($attempt < $attempts) sleep(1 * $attempt);
                    continue;
                }

                $data = json_decode($payload, true);
                if (!is_array($data)) {
                    Log::warning('Invalid JSON from ethereum-client.js, payload: ' . substr($payload, 0, 200), ['attempt' => $attempt]);
                    $lastException = new \RuntimeException('Invalid JSON from ethereum client: ' . substr($payload, 0, 200));
                    if ($attempt < $attempts) {
                        sleep(1 * $attempt);
                        continue;
                    }
                }

                // Additional debug logging for specific RPC actions to aid diagnosis
                try {
                    $op = is_array($args) && count($args) > 0 ? (string)$args[0] : 'unknown';
                    if (in_array($op, ['receipt', 'getTransactionByHash', 'getTransactionByHashWithMeta', 'getTransaction'], true)) {
                        // Log only the important receipt/tx fields to avoid huge payloads and sensitive data
                        $preview = [];
                        if (isset($data['receipt']) && is_array($data['receipt'])) {
                            $r = $data['receipt'];
                            $preview['receipt'] = [
                                'status' => $r['status'] ?? null,
                                'blockNumber' => $r['blockNumber'] ?? ($r['blockNum'] ?? null),
                                'blockHash' => $r['blockHash'] ?? null,
                                'transactionIndex' => $r['transactionIndex'] ?? null,
                                'transactionHash' => $r['transactionHash'] ?? ($r['hash'] ?? null),
                            ];
                        } elseif (isset($data['transaction']) && is_array($data['transaction'])) {
                            $t = $data['transaction'];
                            $preview['transaction'] = [
                                'hash' => $t['hash'] ?? null,
                                'blockNumber' => $t['blockNumber'] ?? ($t['blockNum'] ?? null),
                                'blockHash' => $t['blockHash'] ?? null,
                                'transactionIndex' => $t['transactionIndex'] ?? null,
                                'to' => $t['to'] ?? null,
                            ];
                        } else {
                            // Best-effort: include top-level keys
                            $preview['top'] = array_keys($data);
                        }

                        Log::info('EthereumService rpc response preview', ['operation' => $op, 'preview' => $preview, 'cmd' => $cmd]);
                    }
                } catch (\Throwable $_) {
                    // prevent diagnostic logging from breaking the main flow
                }

                if (isset($data['error'])) {
                    $err = is_string($data['error']) ? $data['error'] : json_encode($data['error']);
                    $message = 'Ethereum client error: ' . $err;
                    Log::warning('Ethereum client returned error payload', ['error' => substr($err, 0, 500), 'attempt' => $attempt]);

                    $isConfigFatal = preg_match('/could not detect network|invalid rpc url|missing .*api key|wrong network|chain mismatch|malformed provider|network mismatch|provider cannot detect network/i', $err) === 1;
                    if ($isConfigFatal) {
                        throw new \RuntimeException('Ethereum provider/network error: ' . $err);
                    }

                    $lastException = new \RuntimeException($message);
                    if ($attempt < $attempts) {
                        sleep(1 * $attempt);
                        continue;
                    }
                    throw $lastException;
                }

                if ($cacheable && $cacheKey !== null) {
                    try {
                        if (count($this->rpcCache) >= $this->rpcCacheMax) {
                            // Evict oldest entry (simple FIFO)
                            array_shift($this->rpcCache);
                        }
                        $this->rpcCache[$cacheKey] = $data;
                    } catch (\Throwable $_) {
                        // Cache failures must not break the main flow
                    }
                }

                return $data;
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning('EthereumService node execution attempt failed', ['attempt' => $attempt, 'message' => $e->getMessage()]);
                if ($attempt < $attempts) {
                    sleep(1 * $attempt);
                    continue;
                }
            }
        }

        Log::error('EthereumService node execution failed after attempts', ['last_error' => $lastException ? $lastException->getMessage() : 'unknown']);
        throw new \RuntimeException('Ethereum node execution failed: ' . ($lastException ? $lastException->getMessage() : 'unknown'));
    }

    /**
     * Check if an address is valid
     * @param string $address
     * @return bool
     */
    public function isValidAddress(string $address): bool
    {
        $normalized = trim((string) $address);
        if ($normalized === '') {
            return false;
        }

        // Address syntax validation does not require any provider/network lookup.
        // Some controller flows validate destination addresses before any queue or
        // RPC-backed balance sync, so attempting a live node call here can crash the
        // request when the underlying provider is transiently unavailable.
        return preg_match('/^0x[0-9a-fA-F]{40}$/', $normalized) === 1;
    }

    /**
     * Explicit zero-address guard
     */
    public function isZeroAddress(?string $address): bool
    {
        if (empty($address)) return false;
        $addr = strtolower(trim((string)$address));
        return $addr === '0x0000000000000000000000000000000000000000';
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
     * Send a JSON-RPC POST (batch or single) directly to the configured ETH RPC URL.
     * Returns decoded JSON as array or throws on fatal errors.
     */
    protected function sendRpcPost(array $payload): array
    {
        $url = $this->resolveRpcUrl();
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($payload),
                'timeout' => 30,
            ],
        ];
        $ctx = stream_context_create($opts);
        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) {
            throw new \RuntimeException('RPC POST failed to ' . $url);
        }
        $decoded = json_decode($res, true);
        if ($decoded === null) {
            throw new \RuntimeException('Invalid JSON RPC response');
        }
        return $decoded;
    }

    /**
     * Batch getTransactionReceipt for multiple tx hashes. Returns map txHash => ['receipt' => ...] or ['receipt'=>null]
     * Falls back to individual runNode calls if batch fails.
     */
    public function batchGetTransactionReceipts(array $txHashes, int $chunkSize = 25): array
    {
        $out = [];
        $txHashes = array_values(array_unique(array_filter($txHashes)));
        if (empty($txHashes)) return $out;

        $idMap = [];
        $chunks = array_chunk($txHashes, $chunkSize);
        foreach ($chunks as $chunkIndex => $chunk) {
            $batch = [];
            $idMapChunk = [];
            foreach ($chunk as $i => $tx) {
                $id = 'b_receipt_' . $chunkIndex . '_' . $i;
                $batch[] = ['jsonrpc' => '2.0', 'id' => $id, 'method' => 'eth_getTransactionReceipt', 'params' => [$tx]];
                $idMapChunk[$id] = $tx;
            }

            try {
                // increment instrumentation: count these ops as rpcCalls and per-op receipt
                self::$rpcCalls += count($batch);
                foreach ($batch as $_) {
                    if (!isset(self::$rpcOps['receipt'])) self::$rpcOps['receipt'] = 0;
                    self::$rpcOps['receipt']++;
                }

                // mark a batch sent for metrics
                self::$batchesSent++;

                $resp = $this->sendRpcPost($batch);
                if (!is_array($resp)) {
                    throw new \RuntimeException('Batch response invalid');
                }

                // Map responses back to tx hashes. Preserve item-level errors if present.
                foreach ($resp as $item) {
                    $id = $item['id'] ?? null;
                    $res = $item['result'] ?? null;
                    $err = $item['error'] ?? null;
                    if ($id !== null && isset($idMapChunk[$id])) {
                        $tx = $idMapChunk[$id];
                        if ($err !== null) {
                            $out[$tx] = ['error' => $err];
                        } else {
                            $out[$tx] = ['receipt' => $res];
                        }
                    }
                }

                // For any tx not present in response, set to null (to preserve old behavior)
                foreach ($chunk as $tx) {
                    if (!isset($out[$tx])) {
                        $out[$tx] = ['receipt' => null];
                    }
                }

            } catch (\Throwable $e) {
                // Batch failed: increment batch failure metric and fallback to individual runNode calls to preserve behavior and instrumentation
                self::$batchFailures++;
                foreach ($chunk as $tx) {
                    try {
                        // runNode expects op 'receipt'
                        $res = $this->runNode(['receipt', $tx]);
                        $out[$tx] = $res;
                    } catch (\Throwable $inner) {
                        // Preserve previous behavior: on RPC failure treat as retryable by returning a special marker
                        $out[$tx] = ['error' => $inner->getMessage()];
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Batch getBlockByNumber for given decimal block numbers. Returns map blockNumber => blockResult (as returned by provider) or null.
     * Falls back to individual runNode calls on failure.
     */
    public function batchGetBlocks(array $blockNumbers, int $chunkSize = 25): array
    {
        $out = [];
        $blockNumbers = array_values(array_unique(array_filter($blockNumbers, fn($v) => $v !== null && $v !== '')));
        if (empty($blockNumbers)) return $out;

        $chunks = array_chunk($blockNumbers, $chunkSize);
        foreach ($chunks as $chunkIndex => $chunk) {
            $batch = [];
            $idMapChunk = [];
            foreach ($chunk as $i => $bn) {
                // convert decimal to hex RPC tag
                $hex = '0x' . dechex((int)$bn);
                $id = 'b_block_' . $chunkIndex . '_' . $i;
                $batch[] = ['jsonrpc' => '2.0', 'id' => $id, 'method' => 'eth_getBlockByNumber', 'params' => [$hex, false]];
                $idMapChunk[$id] = $bn;
            }

            try {
                self::$rpcCalls += count($batch);
                foreach ($batch as $_) {
                    if (!isset(self::$rpcOps['block'])) self::$rpcOps['block'] = 0;
                    self::$rpcOps['block']++;
                }

                // mark a blocks batch sent
                self::$batchesSent++;

                $resp = $this->sendRpcPost($batch);
                if (!is_array($resp)) {
                    throw new \RuntimeException('Batch blocks response invalid');
                }

                foreach ($resp as $item) {
                    $id = $item['id'] ?? null;
                    $res = $item['result'] ?? null;
                    $err = $item['error'] ?? null;
                    if ($id !== null && isset($idMapChunk[$id])) {
                        $bn = $idMapChunk[$id];
                        if ($err !== null) {
                            $out[$bn] = ['error' => $err];
                        } else {
                            $out[$bn] = ['block' => $res];
                        }
                    }
                }

                foreach ($chunk as $bn) {
                    if (!isset($out[$bn])) {
                        $out[$bn] = ['block' => null];
                    }
                }

            } catch (\Throwable $e) {
                // batch failure metric
                self::$batchFailures++;
                foreach ($chunk as $bn) {
                    try {
                        $hex = '0x' . dechex((int)$bn);
                        $res = $this->runNode(['block', $hex]);
                        $out[$bn] = $res;
                    } catch (\Throwable $inner) {
                        $out[$bn] = ['error' => $inner->getMessage()];
                    }
                }
            }
        }

        return $out;
    }
    /**
     * Get ERC-20 token balance for a given contract and wallet address.
     * Returns formatted token units (e.g. 12.340000 for a 6-decimal USDT balance).
     */
    public function getTokenBalance(string $contractAddress, string $address): string
    {
        $res = $this->runNode(['tokenBalance', $contractAddress, $address]);
        return $res['balance'] ?? '0';
    }

    /**
     * Fetch all ERC-20 Transfer events that match the wallet in a specific block range.
     * This avoids interpreting a capped RPC result set as a complete scan window.
     */
    public function getTokenTransfersInRange(string $contractAddress, string $toAddress, int $fromBlock, int $toBlock): array
    {
        $res = $this->runNode(['tokenTransfers', $contractAddress, $toAddress, '0', (string) $fromBlock, (string) $toBlock]);
        return $res['transfers'] ?? [];
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

        // Preferred diagnostic path: if estimate is present, use it
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

        // If diagnose did not return an estimate, attempt a focused estimateGas node call as a safe fallback.
        try {
            $fallback = $this->runNode(['estimateGas', $fromAddress, $toAddress, $amountEth]);
            if (is_array($fallback) && isset($fallback['gasLimit']) && preg_match('/^\d+$/', (string)$fallback['gasLimit'])) {
                $gasLimit = (string)$fallback['gasLimit'];
                $gasPrice = isset($fallback['gasPrice']) ? (string)$fallback['gasPrice'] : null;
                $gasCostWei = null;
                $gasCostEth = null;
                if ($gasPrice !== null && preg_match('/^\d+$/', $gasPrice)) {
                    try {
                        $gasCostWei = bcmul((string)$gasLimit, (string)$gasPrice);
                        $gasCostEth = bcdiv((string)$gasCostWei, '1000000000000000000', 18);
                    } catch (\Throwable $_) {
                        $gasCostWei = null;
                        $gasCostEth = null;
                    }
                }

                return array_merge($fallback, [
                    'estimate' => ['gasLimit' => $gasLimit],
                    'gasCostWei' => $gasCostWei,
                    'gasCostEth' => $gasCostEth,
                ]);
            }
        } catch (\Throwable $e) {
            // If RPC estimate fails (for example the sender account has insufficient funds and the node returns OutOfFunds),
            // provide a conservative fallback estimate so callers can still compute a required gas reserve.
            Log::warning('EthereumService estimateGas fallback failed, using conservative default gasLimit=21000', ['from' => $fromAddress, 'to' => $toAddress, 'amount' => $amountEth, 'error' => $e->getMessage()]);

            $gasLimit = '21000';
            // Use feeData (preferring EIP-1559 maxFeePerGas) to compute a safe gas price if available.
            try {
                $feeData = $this->getFeeData();
                $gasPrice = isset($feeData['maxFeePerGas']) && $feeData['maxFeePerGas'] !== null ? (string)$feeData['maxFeePerGas'] : (isset($feeData['gasPrice']) ? (string)$feeData['gasPrice'] : null);
            } catch (\Throwable $_) {
                $gasPrice = null;
            }

            $gasCostWei = null;
            $gasCostEth = null;
            if ($gasPrice !== null && preg_match('/^\d+$/', (string)$gasPrice)) {
                try {
                    $gasCostWei = bcmul((string)$gasLimit, (string)$gasPrice);
                    $gasCostEth = bcdiv((string)$gasCostWei, '1000000000000000000', 18);
                } catch (\Throwable $_) {
                    $gasCostWei = null;
                    $gasCostEth = null;
                }
            }

            return [
                'estimate' => ['gasLimit' => $gasLimit],
                'gasLimit' => $gasLimit,
                'gasPrice' => $gasPrice,
                'gasCostWei' => $gasCostWei,
                'gasCostEth' => $gasCostEth,
                'estimateError' => $e->getMessage(),
            ];
        }

        if (isset($res['estimateError'])) {
            throw new \RuntimeException(json_encode($res));
        }

        return $res;
    }

    /**
     * Get current gas price (wei string) from provider via node helper.
     * Prefer EIP-1559 maxFeePerGas when available (safe upper bound for estimating total gas cost).
     */
    public function getGasPrice(): string
    {
        $res = $this->runNode(['feeData']);

        $maxFeePerGas = isset($res['maxFeePerGas']) ? trim((string) $res['maxFeePerGas']) : null;
        $gasPrice = isset($res['gasPrice']) ? trim((string) $res['gasPrice']) : null;

        if ($maxFeePerGas !== null && $maxFeePerGas !== '') {
            return $maxFeePerGas;
        }

        if ($gasPrice !== null && $gasPrice !== '') {
            return $gasPrice;
        }

        return '0';
    }

    /**
     * Estimate gas for an ERC-20 token transfer using the node helper.
     * Does not fabricate a fallback gas or fee value when current pricing is unavailable.
     */
    public function estimateTokenGas(string $contractAddress, string $fromAddress, string $toAddress, string $amountToken, ?int $decimals = null): array
    {
        $args = ['estimateTokenGas', $contractAddress, $fromAddress, $toAddress, $amountToken];
        if ($decimals !== null) {
            $args[] = (string)$decimals;
        }

        $res = $this->runNode($args);

        $out = [];
        $out['contractAddress'] = $res['contractAddress'] ?? $contractAddress;
        $out['from'] = $res['from'] ?? $fromAddress;
        $out['to'] = $res['to'] ?? $toAddress;
        $out['amountToken'] = $res['amountToken'] ?? $amountToken;
        $out['decimals'] = isset($res['decimals']) ? (int)$res['decimals'] : $decimals;
        $out['amountRaw'] = $res['amountRaw'] ?? null;
        $out['gasLimit'] = isset($res['gasLimit']) ? (string)$res['gasLimit'] : null;
        $out['gasPrice'] = isset($res['gasPrice']) ? (string)$res['gasPrice'] : null;
        $out['maxFeePerGas'] = isset($res['maxFeePerGas']) ? (string)$res['maxFeePerGas'] : null;
        $out['maxPriorityFeePerGas'] = isset($res['maxPriorityFeePerGas']) ? (string)$res['maxPriorityFeePerGas'] : null;
        $out['feeMode'] = isset($res['feeMode']) ? (string)$res['feeMode'] : null;

        if ($out['gasLimit'] !== null && $out['gasPrice'] !== null) {
            try {
                $gasCostWei = bcmul((string)$out['gasLimit'], (string)$out['gasPrice']);
                $gasCostEth = bcdiv((string)$gasCostWei, '1000000000000000000', 18);
                $out['estimatedGasCostWei'] = (string)$gasCostWei;
                $out['estimatedGasCostEth'] = (string)$gasCostEth;
            } catch (\Throwable $_) {
                $out['estimatedGasCostWei'] = null;
                $out['estimatedGasCostEth'] = null;
            }
        }

        return array_merge($res, $out);
    }

    /**
     * Get current fee data from the provider, preferring EIP-1559 values when available.
     */
    public function getFeeData(): array
    {
        $res = $this->runNode(['feeData']);

        $maxFeePerGas = isset($res['maxFeePerGas']) ? trim((string) $res['maxFeePerGas']) : null;
        $maxPriorityFeePerGas = isset($res['maxPriorityFeePerGas']) ? trim((string) $res['maxPriorityFeePerGas']) : null;
        $gasPrice = isset($res['gasPrice']) ? trim((string) $res['gasPrice']) : null;

        if ($maxFeePerGas !== null && $maxFeePerGas !== '' && $maxPriorityFeePerGas !== null && $maxPriorityFeePerGas !== '') {
            return [
                'feeMode' => 'eip1559',
                'maxFeePerGas' => $maxFeePerGas,
                'maxPriorityFeePerGas' => $maxPriorityFeePerGas,
                'gasPrice' => $gasPrice,
            ];
        }

        if ($gasPrice !== null && $gasPrice !== '') {
            return [
                'feeMode' => 'legacy',
                'maxFeePerGas' => null,
                'maxPriorityFeePerGas' => null,
                'gasPrice' => $gasPrice,
            ];
        }

        throw new \RuntimeException('Unable to obtain current fee data for USDT transfer.');
    }

    /**
     * Validate the token transfer gas estimate and current fee data immediately before sending.
     */
    public function prepareTokenTransfer(string $contractAddress, string $fromAddress, string $toAddress, string $amountToken, ?int $decimals = null, ?string $availableEth = null): array
    {
        $estimate = $this->estimateTokenGas($contractAddress, $fromAddress, $toAddress, $amountToken, $decimals);
        $gasLimit = isset($estimate['gasLimit']) ? trim((string) $estimate['gasLimit']) : null;
        if ($gasLimit === null || !preg_match('/^\d+$/', $gasLimit)) {
            throw new \RuntimeException('Unable to estimate gas for USDT transfer.');
        }

        $feeData = $this->getFeeData();
        $feeMode = $feeData['feeMode'] ?? null;
        if ($feeMode === 'eip1559') {
            $feeValueWei = $feeData['maxFeePerGas'] ?? null;
            $priorityFeeWei = $feeData['maxPriorityFeePerGas'] ?? null;
            if ($feeValueWei === null || !preg_match('/^\d+$/', $feeValueWei) || $priorityFeeWei === null || !preg_match('/^\d+$/', $priorityFeeWei)) {
                throw new \RuntimeException('EIP-1559 fee data is incomplete for USDT transfer.');
            }
        } elseif ($feeMode === 'legacy') {
            $feeValueWei = $feeData['gasPrice'] ?? null;
            if ($feeValueWei === null || !preg_match('/^\d+$/', $feeValueWei)) {
                throw new \RuntimeException('Legacy gas price is unavailable for USDT transfer.');
            }
        } else {
            throw new \RuntimeException('Unable to determine the current gas fee mode for USDT transfer.');
        }

        $marginBps = (int) config('ethereum.gas_safety_margin_bps', 0);
        if ($marginBps > 0) {
            $gasLimit = (string) bcdiv(bcmul((string) $gasLimit, (string) (10000 + $marginBps), 0), '10000', 0);
        }

        $requiredGasWei = bcmul((string) $gasLimit, (string) $feeValueWei, 0);
        $requiredEth = bcdiv((string) $requiredGasWei, '1000000000000000000', 18);

        if ($availableEth !== null) {
            $availableEth = trim((string) $availableEth);
            if ($availableEth === '' || !preg_match('/^\d+(\.\d+)?$/', $availableEth)) {
                throw new \RuntimeException('Unable to calculate available ETH gas balance.');
            }

            if (bccomp($availableEth, $requiredEth, 18) < 0) {
                throw new \RuntimeException('Insufficient ETH balance to pay gas for the USDT transfer.');
            }
        }

        return [
            'contractAddress' => $contractAddress,
            'from' => $fromAddress,
            'to' => $toAddress,
            'amountToken' => $amountToken,
            'decimals' => $decimals,
            'gasLimit' => (string) $gasLimit,
            'feeMode' => $feeMode,
            'gasPrice' => $feeMode === 'legacy' ? (string) $feeValueWei : null,
            'maxFeePerGas' => $feeMode === 'eip1559' ? (string) $feeValueWei : null,
            'maxPriorityFeePerGas' => $feeMode === 'eip1559' ? (string) $priorityFeeWei : null,
            'requiredGasWei' => (string) $requiredGasWei,
            'requiredEth' => (string) $requiredEth,
            'availableEth' => $availableEth,
        ];
    }

    /**
     * Broadcast an ETH transaction signed by the wallet's private key.
     */
    public function sendTransaction(string $privateKey, string $toAddress, string $amountEth, ?int $nonce = null): array
    {
        $resolvedNonce = $this->resolveNonce($nonce);
        $args = ['send', $toAddress, $amountEth];
        if ($resolvedNonce !== null) {
            $args[] = (string) $resolvedNonce;
        }

        $res = $this->runNode($args, $privateKey);
        return $res;
    }

    /**
     * Send an ERC-20 token transfer using the configured USDT_CONTRACT_ADDRESS or the provided contract address.
     * Uses current fee data and a fresh estimate immediately before broadcast.
     * @param string $privateKey
     * @param string $toAddress
     * @param string $amountToken Human-readable token amount (e.g. "12.34")
     * @param string|null $contractAddress Optional contract address; if null, env('USDT_CONTRACT_ADDRESS') is used
     * @return array
     */
    public function sendTokenTransaction(string $privateKey, string $toAddress, string $amountToken, ?string $contractAddress = null, ?int $nonce = null): array
    {
        $contract = $contractAddress ?? env('USDT_CONTRACT_ADDRESS');
        if (empty($contract)) {
            throw new \RuntimeException('USDT contract address not configured. Set USDT_CONTRACT_ADDRESS in .env');
        }

        $from = $this->getSignerAddress($privateKey);
        $ethBalance = $this->getBalance($from);
        $prepared = $this->prepareTokenTransfer($contract, $from, $toAddress, $amountToken, null, $ethBalance);
        $decimals = 6;
        $estimate = $this->estimateTokenGas($contract, $from, $toAddress, $amountToken, $decimals);
        if (isset($estimate['decimals']) && is_numeric($estimate['decimals'])) {
            $decimals = (int) $estimate['decimals'];
        }

        $resolvedNonce = $this->resolveNonce($nonce);
        $args = ['sendToken', $contract, $toAddress, $amountToken, (string)$decimals, (string)$prepared['gasLimit'], $prepared['feeMode']];
        if ($prepared['feeMode'] === 'eip1559') {
            $args[] = (string) ($prepared['maxFeePerGas'] ?? '');
            $args[] = (string) ($prepared['maxPriorityFeePerGas'] ?? '');
        } else {
            $args[] = (string) ($prepared['gasPrice'] ?? '');
        }

        if ($resolvedNonce !== null) {
            $args[] = (string) $resolvedNonce;
        }

        return $this->runNode($args, $privateKey);
    }

    /**
     * Fetch a transaction receipt and confirmations from the Sepolia RPC.
     */
    public function getTransactionReceipt(string $txHash): array
    {
        $res = $this->runNode(['receipt', $txHash]);
        return $res;
    }

    public function getTransactionByHash(string $txHash): array
    {
        $res = $this->runNode(['getTransactionByHash', $txHash]);
        return $res;
    }

    public function getBlock(string $blockIdentifier): array
    {
        $res = $this->runNode(['block', (string) $blockIdentifier]);
        return $res;
    }

    public function diagnoseProvider(): array
    {
        $network = $this->resolveNetworkName();
        $rpcUrl = $this->resolveRpcUrl($network);
        $rpcHost = parse_url($rpcUrl, PHP_URL_HOST) ?: $this->rpcHostForNetwork($network);

        $res = $this->runNode(['diagnose']);

        return [
            'configuredNetwork' => $network,
            'expectedChainId' => $this->getExpectedChainId(),
            'detectedChainId' => isset($res['detectedChainId']) ? (int) $res['detectedChainId'] : (isset($res['chainId']) ? (int) $res['chainId'] : null),
            'detectedNetwork' => $res['detectedNetwork'] ?? ($res['network'] ?? null),
            'rpcHost' => $rpcHost,
            'latestBlock' => $res['latestBlock'] ?? null,
            'provider' => $res['provider'] ?? 'UNKNOWN',
            'usdtContract' => $res['usdtContract'] ?? null,
            'usdtDecimals' => $res['usdtDecimals'] ?? null,
        ];
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
            $args[] = (string)$this->getCurrentBlockNumber();
        }

        $res = $this->runNode($args);
        return $res['transfers'] ?? [];
    }

    /**
     * Normalize EVM receipt.status into a strict success/failure/unknown result.
     *
     * Success: 1, "1", true, "0x1", "0X1"
     * Failure: 0, "0", false, "0x0", "0X0"
     * Unknown: null, empty string, unexpected value
     */
    public function normalizeReceiptStatus(mixed $status): ?bool
    {
        if ($status === null) {
            return null;
        }

        if (is_bool($status)) {
            return $status ? true : false;
        }

        if (is_int($status) || is_float($status)) {
            $numeric = (string) $status;
            if ($numeric === '0') {
                return false;
            }
            if ($numeric === '1') {
                return true;
            }
            return null;
        }

        if (is_string($status)) {
            $trimmed = trim($status);
            if ($trimmed === '') {
                return null;
            }

            $upper = strtoupper($trimmed);
            if ($upper === 'TRUE') {
                return true;
            }
            if ($upper === 'FALSE') {
                return false;
            }
            if ($upper === '0' || $upper === '0X0') {
                return false;
            }
            if ($upper === '1' || $upper === '0X1') {
                return true;
            }

            return null;
        }

        return null;
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

    /**
     * Get transaction count (nonce) for an address from provider. Returns numeric next nonce (as int).
     * @param string $address
     * @param string $tag 'latest' or 'pending'
     * @return int
     */
    public function getTransactionCount(string $address, string $tag = 'pending'): int
    {
        // node helper will return decimal string or numeric
        $res = $this->runNode(['getTransactionCount', $address, $tag]);
        if (isset($res['transactionCount'])) {
            $val = $res['transactionCount'];
            if (is_string($val) && str_starts_with($val, '0x')) {
                return hexdec($val);
            }
            return (int)$val;
        }

        // Fallback: try numeric fields
        if (isset($res['result'])) {
            $val = $res['result'];
            if (is_string($val) && str_starts_with($val, '0x')) {
                return hexdec($val);
            }
            return (int)$val;
        }

        return 0;
    }
}

