<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\WalletController;

$queries = [];
DB::listen(function($query) use (&$queries) {
    $queries[] = [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time ?? null,
    ];
});

// Authenticate as user 60
auth()->loginUsingId(60);

$payload = [
    'sender_wallet_id' => 210,
    'wallet_address' => '0x360Fd699e7BF73383552fE5A8642D549489A53F9',
    'amount' => '0.001',
];

$request = Request::create('/user/send', 'POST', $payload);
$request->server->set('REMOTE_ADDR', '127.0.0.1');

$controller = new WalletController();
try {
    $response = $controller->sendCrypto($request);
    echo json_encode([
        'response_type' => get_class($response),
        'queries' => $queries,
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'class' => get_class($e),
        'queries' => $queries,
    ], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}
