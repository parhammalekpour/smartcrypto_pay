<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\EthereumService;
use App\Models\Deposit;

$eth = app(EthereumService::class);
$rpcUrl = $eth->resolveRpcUrl();
if (empty($rpcUrl)) {
    echo "No RPC URL\n";
    exit(1);
}

// Collect up to 8 recent tx hashes from deposits or transactions
$txs = Deposit::whereNotNull('tx_hash')->orderBy('id','desc')->limit(8)->pluck('tx_hash')->filter()->values()->all();
if (empty($txs)) {
    echo "No tx_hashes found in deposits; trying transactions table...\n";
    $txs = \App\Models\Transaction::whereNotNull('tx_hash')->orderBy('id','desc')->limit(8)->pluck('tx_hash')->filter()->values()->all();
}
if (empty($txs)) {
    echo "No sample transactions available to test.\n";
    exit(0);
}

echo "RPC URL: $rpcUrl\n";
echo "Testing " . count($txs) . " tx receipts\n";

function postJson($url, $payload) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($payload),
            'timeout' => 30,
        ],
    ];
    $ctx = stream_context_create($opts);
    $start = microtime(true);
    $res = @file_get_contents($url, false, $ctx);
    $dur = microtime(true) - $start;
    return [$res === false ? null : $res, $dur, $http_response_header ?? null];
}

// Individual requests for receipts
$individualTimes = [];
$individualResults = [];
foreach ($txs as $tx) {
    $payload = [
        'jsonrpc' => '2.0',
        'id' => uniqid('r_'),
        'method' => 'eth_getTransactionReceipt',
        'params' => [$tx],
    ];
    [$res, $dur, $hdr] = postJson($rpcUrl, $payload);
    $individualTimes[] = $dur;
    $individualResults[$tx] = $res ? json_decode($res, true) : null;
    echo "Individual receipt $tx -> " . round($dur,3) . "s\n";
}
$sumInd = array_sum($individualTimes);
echo "Total individual receipts time: " . round($sumInd,3) . "s\n";

// Batch request for receipts
$batch = [];
foreach ($txs as $i => $tx) {
    $batch[] = [
        'jsonrpc' => '2.0',
        'id' => 'b_r_' . $i,
        'method' => 'eth_getTransactionReceipt',
        'params' => [$tx],
    ];
}
[$bres, $bdur, $bhdr] = postJson($rpcUrl, $batch);
$bresDecoded = $bres ? json_decode($bres, true) : null;
echo "Batch receipts overall -> " . round($bdur,3) . "s\n";

// Extract block numbers from receipts (where available)
$blockNumbers = [];
if ($bresDecoded && is_array($bresDecoded)) {
    foreach ($bresDecoded as $item) {
        $receipt = $item['result'] ?? null;
        if ($receipt && isset($receipt['blockNumber'])) {
            $blockNumbers[] = hexdec($receipt['blockNumber']);
        }
    }
}
$blockNumbers = array_values(array_unique($blockNumbers));
echo "Unique block numbers extracted: " . count($blockNumbers) . "\n";
if (empty($blockNumbers)) {
    echo "No block numbers available from receipts; skipping block tests.\n";
    exit(0);
}

// Individual requests for blocks
$indBlockTimes = [];
foreach ($blockNumbers as $bn) {
    $hex = '0x' . dechex($bn);
    $payload = [
        'jsonrpc' => '2.0',
        'id' => uniqid('bk_'),
        'method' => 'eth_getBlockByNumber',
        'params' => [$hex, false],
    ];
    [$res, $dur, $hdr] = postJson($rpcUrl, $payload);
    $indBlockTimes[] = $dur;
    echo "Individual block $bn -> " . round($dur,3) . "s\n";
}
$sumBlocks = array_sum($indBlockTimes);
echo "Total individual blocks time: " . round($sumBlocks,3) . "s\n";

// Batch request for blocks
$batchBlocks = [];
foreach ($blockNumbers as $i => $bn) {
    $hex = '0x' . dechex($bn);
    $batchBlocks[] = [
        'jsonrpc' => '2.0',
        'id' => 'b_bk_' . $i,
        'method' => 'eth_getBlockByNumber',
        'params' => [$hex, false],
    ];
}
[$bres2, $bdur2, $bhdr2] = postJson($rpcUrl, $batchBlocks);
echo "Batch blocks overall -> " . round($bdur2,3) . "s\n";

// Summary
echo "\nSummary:\n";
echo "Receipts: individual total=" . round($sumInd,3) . "s, batch=" . round($bdur,3) . "s\n";
echo "Blocks: individual total=" . round($sumBlocks,3) . "s, batch=" . round($bdur2,3) . "s\n";

// Estimate combined effect: previously receipts_count + blocks_count RPCs reduced to 2 batch calls
echo "Done.\n";
