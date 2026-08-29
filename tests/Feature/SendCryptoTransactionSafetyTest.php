<?php

namespace Tests\Feature;

use App\Jobs\SendCryptoTransaction;
use App\Jobs\UpdateDepositConfirmationsJob;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\BalanceSyncService;
use App\Services\BlockchainWalletService;
use App\Services\EthereumService;
use App\Services\NonceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SendCryptoTransactionSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWallet(string $address): Wallet
    {
        $userId = $this->createTestUser();
        $privateKey = '0x' . str_repeat('1', 64);
        $derivedAddress = (new BlockchainWalletService())->deriveAddress($privateKey, 'ETH');
        $id = DB::table('wallets')->insertGetId([
            'user_id' => $userId,
            'wallet_address' => $derivedAddress,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
            'currency' => 'ETH',
            'balance' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Wallet::findOrFail($id);
    }

    public function test_send_crypto_transaction_reserves_nonce_before_broadcast(): void
    {
        $wallet = $this->createWallet('0x0000000000000000000000000000000000000101');
        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '0.01',
            'currency' => 'ETH',
            'status' => 'processing',
            'receiver_wallet_address' => '0x0000000000000000000000000000000000000202',
            'nonce' => null,
            'tx_hash' => null,
        ]);

        $ethService = Mockery::mock(EthereumService::class);
        // safety helpers expected by the job
        $ethService->shouldReceive('isZeroAddress')->andReturn(false);
        $ethService->shouldReceive('getSignerAddress')->andReturn($wallet->wallet_address);
        $ethService->shouldReceive('sendTransaction')->once()->with(Mockery::type('string'), '0x0000000000000000000000000000000000000202', '0.01', 7)->andReturn(['txHash' => '0xabc']);

        $nonceManager = Mockery::mock(NonceManager::class);
        $nonceManager->shouldReceive('reserveNonceForWallet')->once()->with(Mockery::type(Wallet::class))->andReturn(7);
        $this->app->instance(NonceManager::class, $nonceManager);

        $wallet->setRelation('user', null);
        $this->instance(EthereumService::class, $ethService);

        $job = new SendCryptoTransaction($tx->id);
        $job->handle($ethService);

        $tx->refresh();
        $this->assertSame(7, $tx->nonce);
        $this->assertSame('pending', $tx->status);
        $this->assertSame('0xabc', $tx->tx_hash);
    }

    public function test_send_crypto_transaction_idempotency_skips_broadcast_when_tx_hash_present(): void
    {
        $wallet = $this->createWallet('0x0000000000000000000000000000000000000102');
        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '0.02',
            'currency' => 'ETH',
            'status' => 'processing',
            'receiver_wallet_address' => '0x0000000000000000000000000000000000000203',
            'tx_hash' => '0xexisting',
        ]);

        $ethService = Mockery::mock(EthereumService::class);
        $ethService->shouldReceive('sendTransaction')->never();

        $job = new SendCryptoTransaction($tx->id);
        $job->handle($ethService);

        $this->assertSame('0xexisting', $tx->fresh()->tx_hash);
    }

    public function test_confirmation_receipt_flow_marks_pending_outbound_transaction_dropped_after_timeout(): void
    {
        $wallet = $this->createWallet('0x0000000000000000000000000000000000000103');
        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '0.03',
            'currency' => 'ETH',
            'status' => 'pending',
            'tx_hash' => '0xdropme',
            'broadcasted_at' => now()->subMinutes(40),
            'nonce' => 11,
        ]);

        $ethService = Mockery::mock(EthereumService::class);
        $ethService->shouldReceive('getTransactionReceipt')->once()->with('0xdropme')->andReturn(['receipt' => null]);
        $ethService->shouldReceive('getTransactionByHash')->once()->with('0xdropme')->andReturn([]);

        $job = new UpdateDepositConfirmationsJob();
        $job->handle($ethService, Mockery::mock(BalanceSyncService::class));

        $this->assertSame('dropped', $tx->fresh()->status);
    }

    public function test_replacement_transaction_is_detected_for_same_nonce_and_flags_original(): void
    {
        $wallet = $this->createWallet('0x0000000000000000000000000000000000000104');
        $original = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '0.04',
            'currency' => 'ETH',
            'status' => 'pending',
            'tx_hash' => '0xa',
            'nonce' => 20,
            'from_address' => $wallet->wallet_address,
        ]);

        $replacement = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '0.05',
            'currency' => 'ETH',
            'status' => 'pending',
            'tx_hash' => '0xb',
            'nonce' => 20,
            'from_address' => $wallet->wallet_address,
        ]);

        $ethService = Mockery::mock(EthereumService::class);
        $ethService->shouldReceive('getCurrentBlockNumber')->andReturn(100);
        $ethService->shouldReceive('getTransactionReceipt')->once()->with('0xa')->andReturn(['receipt' => ['status' => '0x1', 'blockNumber' => '0x64', 'blockHash' => '0xblock', 'transactionIndex' => '0x0', 'gasUsed' => '21000']]);
        $ethService->shouldReceive('normalizeReceiptStatus')->andReturn(true);
        $ethService->shouldReceive('getBlock')->once()->with(100)->andReturn(['hash' => '0xblock']);

        $job = new UpdateDepositConfirmationsJob();
        $job->handle($ethService, Mockery::mock(BalanceSyncService::class));

        $this->assertSame('0xb', $original->fresh()->replaced_by);
        $this->assertSame('0xa', $replacement->fresh()->replacement_of);
    }
}
