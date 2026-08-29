<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

function out($m) { echo $m . PHP_EOL; }

try {
    out("TRACE START");

    $walletId = 210;
    $destination = '0x360Fd699e7BF73383552fE5A8642D549489A53F9';
    $amount = '0.001';

    out("Looking up wallet {$walletId}...");
    $wallet = \App\Models\Wallet::find($walletId);
    if (!$wallet) {
        out("WALLET_NOT_FOUND");
        exit(1);
    }
    out("WALLET_FOUND: id={$wallet->id}, user_id={$wallet->user_id}, currency={$wallet->currency}, address={$wallet->wallet_address}, balance={$wallet->balance}");

    $userId = $wallet->user_id;
    out("Logging in as user {$userId}...");
    Auth::loginUsingId($userId);
    out("AUTH_ID:" . (int)auth()->id());

    // Create a request object
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

    // Inspect DB for newly created transactions for this wallet and user
    try {
        $latestForWallet = \App\Models\Transaction::where('wallet_id', $walletId)->orderBy('created_at', 'desc')->first();
        if ($latestForWallet) {
            out("LATEST_TX_FOR_WALLET: " . json_encode(['id'=>$latestForWallet->id,'amount'=>(string)$latestForWallet->amount,'status'=>$latestForWallet->status,'tx_hash'=>$latestForWallet->tx_hash,'created_at'=>(string)$latestForWallet->created_at]));
        } else {
            out("LATEST_TX_FOR_WALLET: none");
        }

        $latestForUser = \App\Models\Transaction::where('user_id', auth()->id())->orderBy('created_at', 'desc')->first();
        if ($latestForUser) {
            out("LATEST_TX_FOR_USER: " . json_encode(['id'=>$latestForUser->id,'wallet_id'=>$latestForUser->wallet_id,'amount'=>(string)$latestForUser->amount,'status'=>$latestForUser->status,'tx_hash'=>$latestForUser->tx_hash,'created_at'=>(string)$latestForUser->created_at]));
        } else {
            out("LATEST_TX_FOR_USER: none");
        }
    } catch (\Throwable $e) {
        out("DB_QUERY_FAILED: " . $e->getMessage());
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

    out("TRACE END");
} catch (\Throwable $e) {
    out("TRACE_EXCEPTION: " . $e->getMessage());
    out($e->getTraceAsString());
    exit(3);
}
