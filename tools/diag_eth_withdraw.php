<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;
use Illuminate\Support\Facades\DB;

$out = function($m){ echo $m.PHP_EOL; };

$walletId = 210;
$destination = '0x360Fd699e7BF73383552fE5A8642D549489A53F9';
$amount = '0.001';

$out("DIAG START");
$wallet = Wallet::find($walletId);
if (!$wallet) { $out("WALLET_NOT_FOUND"); exit(1); }
$out("Wallet: id={$wallet->id}, address={$wallet->wallet_address}, balance={$wallet->balance}");

$eth = new EthereumService();
$out("Normalized amount: " . (EthereumService::normalizeHumanAmountInput($amount) ?? 'NULL'));

try {
    $wei = $eth->parseEther($amount);
    $out("parseEther wei: " . $wei);
} catch (\Throwable $e) {
    $out("parseEther FAILED: " . $e->getMessage());
    exit(2);
}

try {
    $onchainBalance = trim((string)$eth->getBalance($wallet->wallet_address));
    $out("onchain balance: " . $onchainBalance);
} catch (\Throwable $e) {
    $out("getBalance FAILED: " . $e->getMessage());
    exit(3);
}

try {
    $diag = $eth->estimateGas($wallet->wallet_address, $destination, $amount);
    $out("diagnose output: " . json_encode($diag));
    $gasLimit = isset($diag['estimate']['gasLimit']) ? (string)$diag['estimate']['gasLimit'] : ($diag['gasLimit'] ?? null);
    $gasPrice = isset($diag['gasPrice']) ? (string)$diag['gasPrice'] : ($diag['gasPrice'] ?? null);
    $out("gasLimit={$gasLimit}, gasPrice={$gasPrice}");
    if ($gasLimit === null) { $out("NO_GAS_LIMIT"); exit(4); }
    if ($gasPrice === null) {
        try { $gasPrice = (string)$eth->getGasPrice(); $out("fallback gasPrice={$gasPrice}"); } catch (\Throwable $_) { $gasPrice = null; }
    }
    if ($gasPrice === null) { $out("NO_GAS_PRICE"); exit(5); }

    $gasCostWei = bcmul($gasLimit, $gasPrice, 0);
    $gasCostEth = bcdiv($gasCostWei, '1000000000000000000', 18);
    $out("gasCostWei={$gasCostWei}, gasCostEth={$gasCostEth}");

    $sum = bcadd($amount, $gasCostEth, 18);
    $out("amount+gas = {$sum}");
    $cmp = bccomp($onchainBalance, $sum, 18);
    $out("compare onchain >= amount+gas => cmp={$cmp} (>=0 means sufficient)");
    if ($cmp < 0) { $out("INSUFFICIENT_ONCHAIN_BALANCE"); exit(6); }

} catch (\Throwable $e) {
    $out("diagnose FAILED: " . $e->getMessage());
    exit(7);
}

// Try DB lock and available calculation
try {
    DB::transaction(function() use ($wallet, $amount, $out, $walletId) {
        $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
        $out("locked wallet balance=" . $lockedWallet->balance);

        $dbBalance = (string)$lockedWallet->balance;
        $reservedRow = DB::selectOne('select coalesce(sum(amount),0) as total from transactions where wallet_id = ? and status in (?, ?, ?, ?, ?)', [$lockedWallet->id, 'processing', 'broadcasting', 'pending', 'completed', 'confirmed']);
        $reserved = (string) ($reservedRow->total ?? '0');
        $out("reserved={$reserved}");
        $available = bcsub($dbBalance, $reserved === '' ? '0' : $reserved, 18);
        $out("available={$available}");
        $cmp2 = bccomp($available, $amount, 18);
        $out("compare available >= amount => cmp={$cmp2}");
        if ($cmp2 < 0) {
            throw new RuntimeException('Insufficient local wallet balance to create ETH withdrawal.');
        }
        // If reached, create a dry-run create (but we won't persist). We'll just echo we would create.
        $out("Would create transaction here (dry-run).\n");
    });
} catch (\Throwable $e) {
    $out("DB lock/create FAILED: " . $e->getMessage());
    exit(8);
}

$out("DIAG END - all checks passed (dry-run did not persist a transaction)");
