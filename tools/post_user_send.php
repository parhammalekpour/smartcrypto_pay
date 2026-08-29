<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\WalletController;

// Authenticate as user id 60 (owner of wallet 210 in prior traces)
$user = User::find(60);
if (!$user) { echo json_encode(['error'=>'user_not_found','user_id'=>60]) . PHP_EOL; exit(1); }
auth()->loginUsingId(60);

$payload = [
    'sender_wallet_id' => 210,
    'wallet_address' => '0x360Fd699e7BF73383552fE5A8642D549489A53F9',
    'amount' => '0.001',
];

$request = Request::create('/user/send', 'POST', $payload);
// set ip and server vars to avoid issues
$request->server->set('REMOTE_ADDR','127.0.0.1');

$controller = new WalletController();
try {
    $response = $controller->sendCrypto($request);
    // If it's a RedirectResponse or View, try to summarize
    $out = ['type' => get_class($response)];
    if (method_exists($response, 'getStatusCode')) $out['status_code'] = $response->getStatusCode();
    if (method_exists($response, 'getContent')) $out['content'] = substr((string)$response->getContent(), 0, 2000);

    echo json_encode(['result' => $out], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Throwable $e) {
    echo json_encode([
        'error' => 'exception',
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'trace' => explode("\n", $e->getTraceAsString(), 10)
    ], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}
