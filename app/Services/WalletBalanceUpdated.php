<?php

namespace App\Services;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Models\Wallet;

class WalletBalanceUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public Wallet $wallet;
    public array $balances;

    public function __construct(Wallet $wallet, array $balances)
    {
        $this->wallet = $wallet;
        $this->balances = $balances;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('wallet.' . $this->wallet->id);
    }

    public function broadcastWith()
    {
        return [
            'wallet_id' => $this->wallet->id,
            'balance' => $this->balances['balance'] ?? null,
            'confirmed' => $this->balances['confirmed'] ?? null,
            'pending' => $this->balances['pending'] ?? null,
            'last_scanned_block' => $this->wallet->last_scanned_block ?? null,
        ];
    }
}
