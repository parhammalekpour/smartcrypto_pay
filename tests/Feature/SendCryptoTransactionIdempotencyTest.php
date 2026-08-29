<?php

namespace Tests\Feature;

use App\Jobs\SendCryptoTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EthereumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendCryptoTransactionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(string $currency = 'USDT', ?string $txHash = null, string $status = 'processing'): Transaction
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => $currency,
            'balance' => '100.000000',
        ]);

        return Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'merchant_id' => null,
            'sender_id' => $user->id,
            'recipient_id' => null,
            'type' => 'withdraw',
            'amount' => '10.00',
            'currency' => $currency,
            'status' => $status,
            'reference' => null,
            'description' => 'USDT Sepolia send',
            'sender_wallet_address' => $wallet->wallet_address,
            'receiver_wallet_address' => '0x1111111111111111111111111111111111111111',
            'tx_hash' => $txHash,
            'block_number' => null,
            'confirmations' => 0,
        ]);
    }

    public function test_normal_withdrawal_broadcasts_once(): void
    {
        $transaction = $this->makeTransaction('USDT');

        $eth = Mockery::mock(EthereumService::class);
        // safety helpers used by the job must be mocked to avoid fail-closed behavior
        $eth->shouldReceive('isZeroAddress')->andReturn(false);
        $eth->shouldReceive('getSignerAddress')->andReturn($transaction->sender_wallet_address);
        $eth->shouldReceive('sendTokenTransaction')
            ->once()
            ->with(Mockery::type('string'), '0x1111111111111111111111111111111111111111', '10.00')
            ->andReturn(['txHash' => '0xabc123']);

        (new SendCryptoTransaction($transaction->id))->handle($eth);

        $fresh = $transaction->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertSame('0xabc123', $fresh->tx_hash);
    }

    public function test_retries_after_tx_hash_exists_do_not_broadcast_again(): void
    {
        $transaction = $this->makeTransaction('USDT', '0xexisting');

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isZeroAddress')->andReturn(false);
        $eth->shouldReceive('getSignerAddress')->andReturn($transaction->sender_wallet_address);
        $eth->shouldReceive('sendTokenTransaction')->never();

        (new SendCryptoTransaction($transaction->id))->handle($eth);

        $fresh = $transaction->fresh();
        $this->assertSame('0xexisting', $fresh->tx_hash);
        $this->assertSame('processing', $fresh->status);
    }

    public function test_concurrent_execution_same_withdrawal_only_one_can_broadcast(): void
    {
        $transaction = $this->makeTransaction('USDT');
        $transaction->update(['status' => 'broadcasting']);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isZeroAddress')->andReturn(false);
        $eth->shouldReceive('getSignerAddress')->andReturn($transaction->sender_wallet_address);
        $eth->shouldReceive('sendTokenTransaction')->never();

        (new SendCryptoTransaction($transaction->id))->handle($eth);

        $this->assertSame('broadcasting', $transaction->fresh()->status);
        $this->assertNull($transaction->fresh()->tx_hash);
    }

    public function test_rpc_failure_without_tx_hash_keeps_retry_possible(): void
    {
        $transaction = $this->makeTransaction('USDT');

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isZeroAddress')->andReturn(false);
        $eth->shouldReceive('getSignerAddress')->andReturn($transaction->sender_wallet_address);
        $eth->shouldReceive('sendTokenTransaction')
            ->once()
            ->andThrow(new \RuntimeException('RPC unavailable'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RPC unavailable');

        (new SendCryptoTransaction($transaction->id))->handle($eth);
    }

    public function test_existing_transaction_with_tx_hash_exits_without_broadcasting(): void
    {
        $transaction = $this->makeTransaction('USDT', '0xalready-set', 'pending');

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('isZeroAddress')->andReturn(false);
        $eth->shouldReceive('getSignerAddress')->andReturn($transaction->sender_wallet_address);
        $eth->shouldReceive('sendTokenTransaction')->never();

        (new SendCryptoTransaction($transaction->id))->handle($eth);

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame('0xalready-set', $transaction->fresh()->tx_hash);
    }
}
