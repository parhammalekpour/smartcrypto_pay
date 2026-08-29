<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Jobs\RecoverBroadcastingTransactions;
use App\Services\EthereumService;
use Illuminate\Support\Facades\DB;

use Illuminate\Foundation\Testing\RefreshDatabase;

class BroadcastRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Bus::fake();
    }

    public function test_eth_transaction_recovered_by_nonce_and_addresses()
    {
        // Arrange: create wallet and broadcasting transaction
        $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name' => 'recovery-test',
            'email' => 'recover_eth@example.test',
            'password' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $enc = \Illuminate\Support\Facades\Crypt::encryptString('0x' . str_repeat('1',64));
        $walletId = \Illuminate\Support\Facades\DB::table('wallets')->insertGetId([
            'user_id' => $userId,
            'wallet_address' => '0xAaAa000000000000000000000000000000000000',
            'encrypted_private_key' => $enc,
            'currency' => 'ETH',
            'network' => 'ethereum',
            'balance' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $wallet = Wallet::find($walletId);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdraw',
            'amount' => '1.500000000000000000',
            'currency' => 'ETH',
            'status' => 'broadcasting',
            'sender_wallet_address' => $wallet->wallet_address,
            'to_address' => '0xBbBb000000000000000000000000000000000000',
            'nonce' => 42,
            'last_checked_at' => now()->subMinutes(5),
        ]);

        $eth = Mockery::mock(EthereumService::class);

        // History returns a candidate hash
        $eth->shouldReceive('getTransactionHistory')->once()->with(strtolower($wallet->wallet_address), 50)->andReturn([
            ['hash' => '0xmatchhash', 'from' => $wallet->wallet_address, 'to' => '0xBbBb000000000000000000000000000000000000', 'value' => '1.500000000000000000'],
        ]);

        // getTransactionByHash returns transaction with matching nonce/from/to
        $eth->shouldReceive('getTransactionByHash')->once()->with('0xmatchhash')->andReturn([
            'transaction' => [
                'hash' => '0xmatchhash',
                'nonce' => '42',
                'from' => $wallet->wallet_address,
                'to' => '0xBbBb000000000000000000000000000000000000',
                'blockNumber' => '123'
            ]
        ]);

        // Ensure no broadcast methods are called
        $eth->shouldReceive('sendTransaction')->never();
        $eth->shouldReceive('sendTokenTransaction')->never();

        // Act
        $job = new RecoverBroadcastingTransactions(10);
        $job->handle($eth);

        // Assert
        $txRefreshed = Transaction::find($tx->id);
        $this->assertNotNull($txRefreshed->tx_hash);
        $this->assertEquals('pending', $txRefreshed->status);
        $this->assertEquals('0xmatchhash', $txRefreshed->tx_hash);
    }

    public function test_usdt_transaction_recovered_by_token_transfer_and_nonce()
    {
        Config::set('ethereum.usdt_contract_address', '0xUSDT000000000000000000000000000000000000');

        $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name' => 'recovery-test-usdt',
            'email' => 'recover_usdt@example.test',
            'password' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $enc = \Illuminate\Support\Facades\Crypt::encryptString('0x' . str_repeat('2',64));
        $walletId = \Illuminate\Support\Facades\DB::table('wallets')->insertGetId([
            'user_id' => $userId,
            'wallet_address' => '0xCcCc000000000000000000000000000000000000',
            'encrypted_private_key' => $enc,
            'currency' => 'USDT',
            'network' => 'ethereum',
            'balance' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $wallet = Wallet::find($walletId);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdraw',
            'amount' => '100.000000',
            'currency' => 'USDT',
            'status' => 'broadcasting',
            'sender_wallet_address' => $wallet->wallet_address,
            'to_address' => '0xDdDd000000000000000000000000000000000000',
            'nonce' => 7,
            'last_checked_at' => now()->subMinutes(5),
        ]);

        $eth = Mockery::mock(EthereumService::class);

        $eth->shouldReceive('getTokenTransfers')->once()->with('0xUSDT000000000000000000000000000000000000', strtolower($tx->to_address), 50, null)
            ->andReturn([
                ['hash' => '0xthash', 'from' => $wallet->wallet_address, 'to' => $tx->to_address, 'value' => '100.000000']
            ]);

        $eth->shouldReceive('getTransactionByHash')->once()->with('0xthash')->andReturn([
            'transaction' => ['hash' => '0xthash', 'nonce' => '7', 'from' => $wallet->wallet_address, 'to' => $tx->to_address, 'blockNumber' => '200']
        ]);

        $eth->shouldReceive('sendTransaction')->never();
        $eth->shouldReceive('sendTokenTransaction')->never();

        $job = new RecoverBroadcastingTransactions(10);
        $job->handle($eth);

        $txRefreshed = Transaction::find($tx->id);
        $this->assertEquals('0xthash', $txRefreshed->tx_hash);
        $this->assertEquals('pending', $txRefreshed->status);
    }

    public function test_wrong_sender_or_different_nonce_is_not_recovered()
    {
        $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name' => 'recovery-test-other',
            'email' => 'recover_other@example.test',
            'password' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $enc = \Illuminate\Support\Facades\Crypt::encryptString('0x' . str_repeat('3',64));
        $walletId = \Illuminate\Support\Facades\DB::table('wallets')->insertGetId([
            'user_id' => $userId,
            'wallet_address' => '0xEeEe000000000000000000000000000000000000',
            'encrypted_private_key' => $enc,
            'currency' => 'ETH',
            'network' => 'ethereum',
            'balance' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $wallet = Wallet::find($walletId);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdraw',
            'amount' => '2.000000000000000000',
            'currency' => 'ETH',
            'status' => 'broadcasting',
            'sender_wallet_address' => $wallet->wallet_address,
            'to_address' => '0xFfFf000000000000000000000000000000000000',
            'nonce' => 99,
            'last_checked_at' => now()->subMinutes(5),
        ]);

        $eth = Mockery::mock(EthereumService::class);

        // History returns a tx but with different sender
        $eth->shouldReceive('getTransactionHistory')->once()->with(strtolower($wallet->wallet_address), 50)
            ->andReturn([['hash' => '0xotherhash', 'from' => '0xOTHER', 'to' => $tx->to_address, 'value' => '2.000000000000000000']]);

        $eth->shouldReceive('getTransactionByHash')->once()->with('0xotherhash')->andReturn([
            'transaction' => ['hash' => '0xotherhash', 'nonce' => '99', 'from' => '0xBadSender0000000000000000000000000000', 'to' => $tx->to_address, 'blockNumber' => '321']
        ]);

        $job = new RecoverBroadcastingTransactions(10);
        $job->handle($eth);

        $txRefreshed = Transaction::find($tx->id);
        $this->assertNull($txRefreshed->tx_hash);
        $this->assertEquals('broadcasting', $txRefreshed->status);
    }

    public function test_not_found_keeps_transaction_in_broadcasting_and_updates_last_checked()
    {
        $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name' => 'recovery-test-notfound',
            'email' => 'recover_notfound@example.test',
            'password' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $enc = \Illuminate\Support\Facades\Crypt::encryptString('0x' . str_repeat('4',64));
        $walletId = \Illuminate\Support\Facades\DB::table('wallets')->insertGetId([
            'user_id' => $userId,
            'wallet_address' => '0x1111000000000000000000000000000000000000',
            'encrypted_private_key' => $enc,
            'currency' => 'ETH',
            'network' => 'ethereum',
            'balance' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $wallet = Wallet::find($walletId);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdraw',
            'amount' => '0.100000000000000000',
            'currency' => 'ETH',
            'status' => 'broadcasting',
            'sender_wallet_address' => $wallet->wallet_address,
            'to_address' => '0x2222000000000000000000000000000000000000',
            'nonce' => 5,
            'last_checked_at' => now()->subMinutes(10),
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getTransactionHistory')->once()->with(strtolower($wallet->wallet_address), 50)->andReturn([]);

        $job = new RecoverBroadcastingTransactions(10);
        $job->handle($eth);

        $txRefreshed = Transaction::find($tx->id);
        $this->assertNull($txRefreshed->tx_hash);
        $this->assertEquals('broadcasting', $txRefreshed->status);
        $this->assertTrue($txRefreshed->last_checked_at !== null);
    }
}
