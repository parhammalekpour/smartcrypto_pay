<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Wallet;

// Get wallet 210
$wallet = Wallet::find(210);
if ($wallet) {
    echo "=== Wallet 210 Details ===\n";
    echo "User ID: " . $wallet->user_id . "\n";
    echo "Currency: " . $wallet->currency . "\n";
    echo "Balance: " . $wallet->balance . "\n";
    echo "Has wallet address: " . (!empty($wallet->wallet_address) ? "Yes" : "No") . "\n";
    echo "Wallet address: " . $wallet->wallet_address . "\n";
    
    // Check if user is verified and 2FA enabled
    $user = $wallet->user;
    if ($user) {
        echo "\n=== User Details ===\n";
        echo "Email: " . $user->email . "\n";
        echo "Email verified at: " . $user->email_verified_at . "\n";
        
        $twoFactor = \App\Models\TwoFactor::where('user_id', $user->id)->first();
        if ($twoFactor) {
            echo "2FA enabled at: " . $twoFactor->enabled_at . "\n";
        } else {
            echo "2FA: Not set up\n";
        }
    }
} else {
    echo "Wallet 210 not found\n";
}
