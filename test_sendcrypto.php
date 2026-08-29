<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Auth;

// Get test user (user_id = 60, wallet_id = 210)
$user = User::find(60);
if (!$user) {
    die("User 60 not found\n");
}

// Create fake 2FA token (for testing, since we don't know real token)
$twoFactor = \App\Models\TwoFactor::where('user_id', $user->id)->first();

echo "=== Simulating sendCrypto Form Submission ===\n";
echo "User: " . $user->email . "\n";
echo "2FA Status: " . ($twoFactor && $twoFactor->enabled_at ? "Enabled" : "Not enabled") . "\n\n";

// Create fake request
$request = new Request([
    'sender_wallet_id' => '210',
    'wallet_address' => '0x360Fd699e7BF73383552fE5A8642D549489A53F9',
    'amount' => '0.001',
    'two_factor_token' => '000000', // Placeholder - INVALID!
    'description' => 'Test withdrawal',
    '_token' => 'test',
]);

// Manually set user
Auth::setUser($user);
request()->setUserResolver(function () use ($user) {
    return $user;
});

// Call sendCrypto method
$controller = new WalletController();

try {
    $response = $controller->sendCrypto($request);
    echo "Response type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "Redirect URL: " . $response->getTargetUrl() . "\n";
        
        // Get session errors
        $sessionData = session()->all();
        echo "Session errors: " . json_encode($sessionData['errors'] ?? []) . "\n";
        echo "Old input: " . json_encode($sessionData['_old_input'] ?? []) . "\n";
    } else if ($response instanceof \Illuminate\Http\JsonResponse) {
        echo "JSON Response: " . $response->getContent() . "\n";
    } else {
        echo "Response: " . $response . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

