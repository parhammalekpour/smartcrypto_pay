<?php

namespace Tests\Feature;

use App\Jobs\SendCryptoTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EthereumService;
use App\Services\BlockchainWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class RpcFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_rpc_failure_keeps_transaction_retryable()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = new BlockchainWalletService();
        $privateKey = '0x' . str_repeat('3', 64);
        $derived = $service->deriveAddress($privateKey, 'ETH');

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'ETH',
            'balance' => '1.000000000000000000',
            'wallet_address' => $derived,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
        ]);

        $tx = Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'type' => 'withdraw', 'amount' => '0.01', 'currency' => 'ETH', 'status' => 'processing', 'receiver_wallet_address' => '0x' . str_repeat('5', 40)]);

        $eth = Mockery::mock(EthereumService::class);
                // Ensure zero-address check passes
                $eth->shouldReceive('isZeroAddress')->andReturn(false);
                $eth->shouldReceive('getSignerAddress')->andReturn($wallet->wallet_address);
                $eth->shouldReceive('sendTransaction')->once()->andThrow(new \RuntimeException('RPC unavailable'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RPC unavailable');

        $job = new SendCryptoTransaction($tx->id);
        $job->handle($eth);

        $this->assertSame('broadcasting', $tx->fresh()->status);
        $this->assertNull($tx->fresh()->tx_hash);
    }
}
