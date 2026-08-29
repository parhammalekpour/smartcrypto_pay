<?php

namespace Tests\Feature;

use App\Http\Controllers\WalletController;
use App\Jobs\SendCryptoTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\EthereumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class ZeroAddressTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWallet(): Wallet
    {
        $user = User::factory()->create();
        $uniqueAddress = '0x' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 40);
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USDT',
            'balance' => '100.000000',
            'wallet_address' => $uniqueAddress,
            'encrypted_private_key' => encrypt('0x' . str_repeat('1', 64)),
        ]);
        $this->actingAs($user);
        return $wallet;
    }

    public function test_controller_rejects_zero_address()
    {
        $wallet = $this->makeUserWallet();

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isValidAddress')->andReturn(true);
        $eth->shouldReceive('isZeroAddress')->andReturn(true);

        Bus::fake();

        $controller = new WalletController();
        $method = new \ReflectionMethod(WalletController::class, 'processUsdtWithdrawalV2');
        $method->setAccessible(true);

        $request = new Request();

        $response = $method->invoke($controller, $wallet, '0x0000000000000000000000000000000000000000', '1.000000', $eth, $request);

        $this->assertDatabaseCount('transactions', 0);
        Bus::assertNotDispatched(SendCryptoTransaction::class);
    }

    public function test_job_rejects_zero_address()
    {
        $user = User::factory()->create();
        $uniqueAddress = '0x' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 40);
        $wallet = Wallet::create(['user_id' => $user->id, 'currency' => 'USDT', 'balance' => '100.000000', 'wallet_address' => $uniqueAddress, 'encrypted_private_key' => encrypt('0x' . str_repeat('1', 64))]);
        $this->actingAs($user);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'withdraw',
            'amount' => '1.000000',
            'currency' => 'USDT',
            'status' => 'processing',
            'receiver_wallet_address' => '0x0000000000000000000000000000000000000000',
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isZeroAddress')->with('0x0000000000000000000000000000000000000000')->andReturn(true);
        $eth->shouldReceive('getSignerAddress')->never();
        $eth->shouldReceive('sendTokenTransaction')->never();

        $job = new SendCryptoTransaction($tx->id);

        $job->handle($eth);

        $this->assertSame('failed', $tx->fresh()->status);
        $this->assertNull($tx->fresh()->tx_hash);
    }

    public function test_ethereum_service_zero_address_helper()
    {
        $eth = new EthereumService();
        $this->assertTrue($eth->isZeroAddress('0x0000000000000000000000000000000000000000'));
        $this->assertFalse($eth->isZeroAddress('0x1111111111111111111111111111111111111111'));
    }
}
