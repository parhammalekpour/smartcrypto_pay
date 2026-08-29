<?php

namespace Tests\Unit;

use App\Models\Wallet;
use App\Models\WalletNonce;
use App\Services\NonceManager;
use App\Services\EthereumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class NonceManagerTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser()
    {
        return \DB::table('users')->insertGetId([
            'name' => 'test',
            'email' => uniqid('test').'@example.test',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function mockEthServiceReturn($value)
    {
        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getTransactionCount')->andReturn($value);
        return $eth;
    }

    public function test_initial_reservation_when_no_row_exists()
    {
        // Create wallet
                $userId = $this->createTestUser();
                $walletId = \DB::table('wallets')->insertGetId([ 'user_id' => $userId, 'currency' => 'ETH', 'wallet_address' => '0x0000000000000000000000000000000000000001', 'encrypted_private_key' => 'DUMMY', 'balance' => 0, 'created_at' => now(), 'updated_at' => now() ]);
                $wallet = Wallet::find($walletId);

        $eth = $this->mockEthServiceReturn(5);
        $mgr = new NonceManager($eth);

        $reserved = $mgr->reserveNonceForWallet($wallet);

        $this->assertSame(5, $reserved);

        $row = WalletNonce::where('wallet_id', $wallet->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(6, (int)$row->next_nonce);
    }

    public function test_reservation_with_existing_row_uses_stored_next_nonce()
    {
        $userId = $this->createTestUser();
                $walletId = \DB::table('wallets')->insertGetId([ 'user_id' => $userId, 'currency' => 'ETH', 'wallet_address' => '0x0000000000000000000000000000000000000002', 'encrypted_private_key' => 'DUMMY', 'balance' => 0, 'created_at' => now(), 'updated_at' => now() ]);
                $wallet = Wallet::find($walletId);
        WalletNonce::create([ 'wallet_id' => $wallet->id, 'address' => '0x0000000000000000000000000000000000000002', 'next_nonce' => 6 ]);

        $eth = $this->mockEthServiceReturn(3); // chain below stored
        $mgr = new NonceManager($eth);

        $reserved = $mgr->reserveNonceForWallet($wallet);
        $this->assertSame(6, $reserved);

        $row = WalletNonce::where('wallet_id', $wallet->id)->first();
        $this->assertSame(7, (int)$row->next_nonce);
    }

    public function test_reconciliation_when_stored_behind_chain()
    {
        $userId = $this->createTestUser();
                $walletId = \DB::table('wallets')->insertGetId([ 'user_id' => $userId, 'currency' => 'ETH', 'wallet_address' => '0x0000000000000000000000000000000000000003', 'encrypted_private_key' => 'DUMMY', 'balance' => 0, 'created_at' => now(), 'updated_at' => now() ]);
                $wallet = Wallet::find($walletId);
        WalletNonce::create([ 'wallet_id' => $wallet->id, 'address' => '0x0000000000000000000000000000000000000003', 'next_nonce' => 5 ]);

        $eth = $this->mockEthServiceReturn(8); // chain ahead
        $mgr = new NonceManager($eth);

        $reserved = $mgr->reserveNonceForWallet($wallet);
        $this->assertSame(8, $reserved);

        $row = WalletNonce::where('wallet_id', $wallet->id)->first();
        $this->assertSame(9, (int)$row->next_nonce);
    }

    public function test_stored_ahead_of_chain_is_not_overwritten_silently()
    {
        $userId = $this->createTestUser();
                $walletId = \DB::table('wallets')->insertGetId([ 'user_id' => $userId, 'currency' => 'ETH', 'wallet_address' => '0x0000000000000000000000000000000000000004', 'encrypted_private_key' => 'DUMMY', 'balance' => 0, 'created_at' => now(), 'updated_at' => now() ]);
                $wallet = Wallet::find($walletId);
        WalletNonce::create([ 'wallet_id' => $wallet->id, 'address' => '0x0000000000000000000000000000000000000004', 'next_nonce' => 12 ]);

        $eth = $this->mockEthServiceReturn(8); // chain behind
        $mgr = new NonceManager($eth);

        $reserved = $mgr->reserveNonceForWallet($wallet);
        $this->assertSame(12, $reserved);

        $row = WalletNonce::where('wallet_id', $wallet->id)->first();
        $this->assertSame(13, (int)$row->next_nonce);
    }

    public function test_two_consecutive_reservations_produce_incrementing_nonces()
    {
        $userId = $this->createTestUser();
                $walletId = \DB::table('wallets')->insertGetId([ 'user_id' => $userId, 'currency' => 'ETH', 'wallet_address' => '0x0000000000000000000000000000000000000005', 'encrypted_private_key' => 'DUMMY', 'balance' => 0, 'created_at' => now(), 'updated_at' => now() ]);
                $wallet = Wallet::find($walletId);

        $eth = $this->mockEthServiceReturn(20);
        $mgr = new NonceManager($eth);

        $a = $mgr->reserveNonceForWallet($wallet);
        $b = $mgr->reserveNonceForWallet($wallet);

        $this->assertSame(20, $a);
        $this->assertSame(21, $b);

        $row = WalletNonce::where('wallet_id', $wallet->id)->first();
        $this->assertSame(22, (int)$row->next_nonce);
    }
}
