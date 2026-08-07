<?php

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use App\Models\Wallet;

Broadcast::channel('wallet.{walletId}', function ($user, $walletId) {
    // Allow when the authenticated user owns the wallet (user_id) or is the merchant
    $wallet = Wallet::find($walletId);
    if (!$wallet) return false;

    if ($wallet->user_id && $wallet->user_id === $user->id) return true;
    if ($wallet->owner_type && strtolower($wallet->owner_type) === 'merchant' && $wallet->owner_id === $user->id) return true;

    // Add admin role bypass if needed
    if (method_exists($user, 'isAdmin') && $user->isAdmin()) return true;

    return false;
});
