<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;

class FindIncompleteWallets extends Command
{
    protected $signature = 'wallet:find-incomplete';

    protected $description = 'Report wallets missing an address or encrypted private key without revealing sensitive private key material.';

    public function handle()
    {
        $query = Wallet::query()
            ->whereNull('wallet_address')
            ->orWhereNull('encrypted_private_key')
            ->orWhere('wallet_address', '')
            ->orWhere('encrypted_private_key', '');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No incomplete wallets found.');
            return 0;
        }

        $this->warn("Found {$count} incomplete wallet(s):");

        $this->table(
            ['id', 'user_id', 'currency', 'wallet_address'],
            $query->get(['id', 'user_id', 'currency', 'wallet_address'])->toArray()
        );

        $this->line('These wallets are flagged as incomplete because they are missing either wallet_address or encrypted_private_key.');
        $this->line('Do not assign a new private key to an existing wallet address without recovering the original key for that address.');

        return 0;
    }
}
