<?php
// Safely set DEPLOYER_PRIVATE_KEY in .env from Wallet ID 183
// - Does NOT print/log/expose the private key
// - Verifies derived address matches expected
// - Writes to project .env only

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;

$walletId = 183;
$expectedAddress = '0x7B48DC6A00C1c93eB3B28feB27A319943b039f3b';
$envPath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . '.env';

$output = ['success' => false, 'message' => ''];

try {
    $wallet = Wallet::find($walletId);
    if (!$wallet) {
        $output['message'] = 'wallet_not_found';
        echo json_encode($output) . PHP_EOL;
        exit(1);
    }

    $privateKey = $wallet->getPrivateKey(); // normalized '0x...'
    if ($privateKey === null) {
        $output['message'] = 'could_not_decrypt_private_key_or_invalid';
        echo json_encode($output) . PHP_EOL;
        exit(1);
    }

    // Verify derived address without exposing the key
    $eth = new EthereumService();
    try {
        $derived = $eth->getSignerAddress($privateKey);
    } catch (\Throwable $e) {
        $output['message'] = 'derive_address_failed';
        echo json_encode($output) . PHP_EOL;
        exit(1);
    }

    if ($derived !== $expectedAddress) {
        $output['message'] = 'derived_address_mismatch';
        echo json_encode($output) . PHP_EOL;
        exit(1);
    }

    // Read .env and replace/add DEPLOYER_PRIVATE_KEY
    if ($envPath === false || !file_exists($envPath)) {
        $output['message'] = '.env_not_found_at_' . (__DIR__ . '/../');
        echo json_encode($output) . PHP_EOL;
        exit(1);
    }

    $envContents = file_get_contents($envPath);
    if ($envContents === false) {
        $output['message'] = 'failed_to_read_env';
        echo json_encode($output) . PHP_EOL;
        exit(1);
    }

    $line = 'DEPLOYER_PRIVATE_KEY=' . $privateKey;

    // Replace existing line if present
    if (preg_match('/^DEPLOYER_PRIVATE_KEY=.*$/m', $envContents)) {
        $newContents = preg_replace('/^DEPLOYER_PRIVATE_KEY=.*$/m', $line, $envContents);
    } else {
        // Append with newline if not ending with newline
        $newContents = rtrim($envContents, "\r\n") . PHP_EOL . $line . PHP_EOL;
    }

    $res = file_put_contents($envPath, $newContents, LOCK_EX);
    if ($res === false) {
        $output['message'] = 'failed_to_write_env';
        echo json_encode($output) . PHP_EOL;
        exit(1);
    }

    // Success - DO NOT output the key
    $output['success'] = true;
    $output['message'] = 'DEPLOYER_PRIVATE_KEY_configured';
    echo json_encode($output) . PHP_EOL;
    exit(0);
} catch (\Throwable $e) {
    $output['message'] = 'unexpected_error';
    $output['error'] = $e->getMessage();
    echo json_encode($output) . PHP_EOL;
    exit(1);
}
