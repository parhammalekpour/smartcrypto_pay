<?php
// Non-destructive check for Wallet ID 183
// - Does NOT print or log private key
// - Uses existing Laravel app bootstrapping and services

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;

$walletId = 183;
$expectedAddress = '0x7B48DC6A00C1c93eB3B28feB27A319943b039f3b';

$report = [
    'all_passed' => false,
    'exists' => false,
    'wallet_address' => null,
    'address_exact_match_expected' => false,
    'currency_is_ETH' => false,
    'network_is_ethereum' => false,
    'has_decrypted_private_key' => false,
    'derived_address_matches_wallet' => false,
    'on_chain_balance' => null,
    'errors' => []
];

try {
    $wallet = Wallet::find($walletId);
    if (!$wallet) {
        $report['errors'][] = 'wallet_not_found';
        echo json_encode($report) . PHP_EOL;
        exit(0);
    }

    $report['exists'] = true;
    $report['wallet_address'] = $wallet->wallet_address;
    $report['address_exact_match_expected'] = ($wallet->wallet_address === $expectedAddress);
    $report['currency_is_ETH'] = (strtoupper((string)$wallet->currency) === 'ETH');
    $report['network_is_ethereum'] = (strtolower((string)$wallet->network) === 'ethereum');

    // Decrypt private key internally (Wallet::getPrivateKey) but DO NOT expose it
    $privateKeyNormalized = $wallet->getPrivateKey(); // normalized like 0x...
    if ($privateKeyNormalized === null) {
        $report['errors'][] = 'could_not_decrypt_private_key_or_invalid';
        echo json_encode($report) . PHP_EOL;
        exit(0);
    }

    $report['has_decrypted_private_key'] = true;

    // Derive signer address using EthereumService which uses the node helper without logging private key
    $eth = new EthereumService();
    try {
        $derived = $eth->getSignerAddress($privateKeyNormalized);
    } catch (\Throwable $e) {
        $report['errors'][] = 'derive_address_failed';
        $report['errors'][] = $e->getMessage();
        echo json_encode($report) . PHP_EOL;
        exit(0);
    }

    $report['derived_address'] = $derived;
    $report['derived_address_matches_wallet'] = ($derived === $wallet->wallet_address);

    // Get on-chain balance (Sepolia) for the wallet address
    try {
        $balance = $eth->getBalance($wallet->wallet_address);
        $report['on_chain_balance'] = $balance;
    } catch (\Throwable $e) {
        $report['errors'][] = 'get_balance_failed';
        $report['errors'][] = $e->getMessage();
        echo json_encode($report) . PHP_EOL;
        exit(0);
    }

    // Final all_passed check (strict exact matches as requested)
    $report['all_passed'] = (
        $report['exists'] === true &&
        $report['address_exact_match_expected'] === true &&
        $report['currency_is_ETH'] === true &&
        $report['network_is_ethereum'] === true &&
        $report['has_decrypted_private_key'] === true &&
        $report['derived_address_matches_wallet'] === true
    );

    echo json_encode($report) . PHP_EOL;
    exit(0);
} catch (\Throwable $e) {
    $report['errors'][] = 'unexpected_error';
    $report['errors'][] = $e->getMessage();
    echo json_encode($report) . PHP_EOL;
    exit(1);
}
