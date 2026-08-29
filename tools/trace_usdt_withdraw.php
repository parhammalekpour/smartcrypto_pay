<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

function out($m) { echo $m . PHP_EOL; }

try {
    out("USDT TRACE START");

    $wallet = \App\Models\Wallet::where('currency', 'USDT')->where('balance', '>', 0)->first();
    if (!$wallet) {
        out("NO_USDT_WALLET_FOUND");
        exit(1);
    }

    $walletId = $wallet->id;
    $destination = '0x360Fd699e7BF73383552fE5A8642D549489A53F9';
    $amount = (string) min((float)$wallet->balance, 1.0);

    out("Using wallet id={$walletId}, address={$wallet->wallet_address}, balance={$wallet->balance}");

    $userId = $wallet->user_id;
    out("Logging in as user {$userId}...");
    Auth::loginUsingId($userId);
    out("AUTH_ID:" . (int)auth()->id());

    $req = Request::create('/user/send', 'POST', [
        'sender_wallet_id' => $walletId,
        'wallet_address' => $destination,
        'amount' => $amount,
        '_token' => csrf_token(),
    ]);

    out("Calling WalletController::sendCrypto()...");
    $controller = new \App\Http\Controllers\WalletController();

    ob_start();
    try {
        $response = $controller->sendCrypto($req);
    } catch (\Throwable $e) {
        $body = ob_get_clean();
        out("CONTROLLER_THROWN: " . $e->getMessage());
        out("STACK: " . $e->getTraceAsString());
        exit(2);
    }
    $body = ob_get_clean();

    out("Controller returned: " . (is_object($response) ? get_class($response) : gettype($response)));
    if (is_object($response) && method_exists($response, 'getStatusCode')) {
        out("Response status: " . $response->getStatusCode());
    }

    $latestForWallet = \App\Models\Transaction::where('wallet_id', $walletId)->orderBy('created_at', 'desc')->first();
    if ($latestForWallet) {
        out("LATEST_TX_FOR_WALLET: " . json_encode(['id'=>$latestForWallet->id,'amount'=>(string)$latestForWallet->amount,'status'=>$latestForWallet->status,'tx_hash'=>$latestForWallet->tx_hash,'created_at'=>(string)$latestForWallet->created_at]));
    } else {
        out("LATEST_TX_FOR_WALLET: none");
    }

    out("Now reading latest laravel log entries (tail 200 lines)...");
    $logPath = __DIR__ . '/../storage/logs/laravel.log';
    if (file_exists($logPath)) {
        $lines = explode("\n", trim(@file_get_contents($logPath)?:''));
        $tail = array_slice($lines, -200);
        foreach ($tail as $l) echo $l . PHP_EOL;
    } else {
        out("No laravel.log found at {$logPath}");
    }

    out("USDT TRACE END");
} catch (\Throwable $e) {
    out("TRACE_EXCEPTION: " . $e->getMessage());
    out($e->getTraceAsString());
    exit(3);
}
