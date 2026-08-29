<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;

$ethService = new EthereumService();
$wallets = Wallet::all();

$report = [];
foreach ($wallets as $w) {
    $entry = [
        'id' => $w->id,
        'address' => $w->wallet_address,
        'currency' => strtoupper($w->currency ?? ''),
        'db_balance' => (string)$w->balance,
        'ui_balance' => (string)$w->balance,
        'alchemy_balance' => null,
        'difference' => null,
        'note' => null,
    ];

    if ($entry['currency'] === 'ETH') {
        try {
            $onchain = $ethService->getBalance($entry['address']);
            $entry['alchemy_balance'] = (string)$onchain;
            // compute numeric difference with bcsub if available
            if (function_exists('bcsub')) {
                $entry['difference'] = bcsub($entry['alchemy_balance'], $entry['db_balance'], 18);
            } else {
                $entry['difference'] = (string)(((float)$entry['alchemy_balance']) - ((float)$entry['db_balance']));
            }
        } catch (\Throwable $e) {
            $entry['note'] = 'Failed to fetch on-chain balance: ' . $e->getMessage();
        }
    } elseif ($entry['currency'] === 'USDT') {
        $entry['note'] = 'USDT token balance check not implemented in EthereumService (USDT_CONTRACT_ADDRESS missing or tool does not fetch token balances)';
    } elseif ($entry['currency'] === 'BTC') {
        $entry['note'] = 'BTC balance cannot be retrieved via Alchemy (Bitcoin network)';
    } else {
        $entry['note'] = 'Unsupported currency for on-chain check via Alchemy';
    }

    $report[] = $entry;
}

foreach ($report as $r) {
    echo json_encode($r) . PHP_EOL;
}
