<?php

namespace Tests\Feature;

use App\Jobs\SendCryptoTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\NonceManager;
use App\Services\EthereumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NonceReuseReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_nonce_reuse_when_already_set()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $privateKey = '0x' . str_repeat('1', 64);
        $service = new \App\Services\BlockchainWalletService();
        $derivedAddress = $service->deriveAddress($privateKey, 'ETH');
        $wallet = Wallet::create(['user_id' => $user->id, 'currency' => 'ETH', 'balance' => '1.000000000000000000', 'wallet_address' => $derivedAddress, 'encrypted_private_key' => \Illuminate\Support\Facades\Crypt::encryptString($privateKey)]);

        $tx = Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'type' => 'withdraw', 'amount' => '0.01', 'currency' => 'ETH', 'status' => 'processing', 'nonce' => 25, 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);

        // Mock NonceManager to throw if called
        $nonceManager = Mockery::mock(NonceManager::class);
        $nonceManager->shouldReceive('reserveNonceForWallet')->never();
        $this->app->instance(NonceManager::class, $nonceManager);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isZeroAddress')->andReturn(false);
        $eth->shouldReceive('getSignerAddress')->andReturn($wallet->wallet_address);
        $eth->shouldReceive('sendTransaction')->once()->andReturn(['txHash' => '0xdead']);

        $job = new SendCryptoTransaction($tx->id);
        $job->handle($eth);

        $this->assertSame(25, $tx->fresh()->nonce);
        $this->assertSame('pending', $tx->fresh()->status);
        $this->assertSame('0xdead', $tx->fresh()->tx_hash);
    }

    public function test_nonce_reservation_when_null_and_not_repeated_on_retry()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $privateKey2 = '0x' . str_repeat('2', 64);
        $service2 = new \App\Services\BlockchainWalletService();
        $derivedAddress2 = $service2->deriveAddress($privateKey2, 'ETH');
        $wallet = Wallet::create(['user_id' => $user->id, 'currency' => 'ETH', 'balance' => '1.000000000000000000', 'wallet_address' => $derivedAddress2, 'encrypted_private_key' => \Illuminate\Support\Facades\Crypt::encryptString($privateKey2)]);

        $tx = Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'type' => 'withdraw', 'amount' => '0.01', 'currency' => 'ETH', 'status' => 'processing', 'nonce' => null, 'receiver_wallet_address' => '0x3333333333333333333333333333333333333333']);

        $nonceManager = Mockery::mock(NonceManager::class);
        $nonceManager->shouldReceive('reserveNonceForWallet')->once()->andReturn(30);
        $this->app->instance(NonceManager::class, $nonceManager);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isZeroAddress')->andReturn(false);
        $eth->shouldReceive('getSignerAddress')->andReturn($wallet->wallet_address);
        $eth->shouldReceive('sendTransaction')->once()->andReturn(['txHash' => '0xbeef']);

        $job = new SendCryptoTransaction($tx->id);
        $job->handle($eth);

        $this->assertSame(30, $tx->fresh()->nonce);
        $this->assertSame('pending', $tx->fresh()->status);
        $this->assertSame('0xbeef', $tx->fresh()->tx_hash);

        // Retry: NonceManager should not be called again
        $nonceManager2 = Mockery::mock(NonceManager::class);
        $nonceManager2->shouldReceive('reserveNonceForWallet')->never();
        $this->app->instance(NonceManager::class, $nonceManager2);

        $eth2 = Mockery::mock(EthereumService::class);
        $eth2->shouldReceive('getSignerAddress')->andReturn($wallet->wallet_address);
        $eth2->shouldReceive('sendTransaction')->never(); // because tx_hash exists

        $job2 = new SendCryptoTransaction($tx->id);
        $job2->handle($eth2);

        $this->assertSame(30, $tx->fresh()->nonce);
    }
}
