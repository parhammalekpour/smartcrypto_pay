<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BlockchainWalletService;
use App\Services\BalanceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class BalanceInvariantTest extends TestCase
{
    use RefreshDatabase;

    private function makeValidWallet(string $currency = 'USDT', string $privateKey = null): Wallet
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = new BlockchainWalletService();
        $pk = $privateKey ?? ('0x' . str_repeat('1', 64));
        $derived = $service->deriveAddress($pk, 'ETH');

        return Wallet::create([
            'user_id' => $user->id,
            'currency' => $currency,
            'balance' => '100.000000',
            'wallet_address' => $derived,
            'encrypted_private_key' => Crypt::encryptString($pk),
        ]);
    }

    public function test_display_balance_prefers_onchain_value_when_available()
    {
        $wallet = $this->makeValidWallet('USDT');
        $wallet->onchain_balance = '125.500000';
        $wallet->balance = '95.000000';
        $wallet->save();

        $this->assertSame('125.500000', $wallet->fresh()->display_balance);
    }

    public function test_broadcasting_withdrawal_is_deducted_from_spendable_balance()
    {
        $wallet = $this->makeValidWallet('USDT');

        Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '60.000000',
            'currency' => 'USDT',
            'status' => 'broadcasting',
            'receiver_wallet_address' => '0x1111111111111111111111111111111111111111',
        ]);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');
        $service = new BalanceSyncService($eth);

        $this->assertSame('40.000000', $service->calculateWalletBalance($wallet)['balance']);
    }

    public function test_spendable_calculation_considers_processing_and_pending_once()
    {
        $wallet = $this->makeValidWallet('USDT');

        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '20.000000', 'currency' => 'USDT', 'status' => 'processing', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '30.000000', 'currency' => 'USDT', 'status' => 'pending', 'receiver_wallet_address' => '0x2222222222222222222222222222222222222222']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $results = (new BalanceSyncService($eth))->calculateWalletBalance($wallet);
        $this->assertSame('50.000000', $results['balance']);

        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '20.000000', 'currency' => 'USDT', 'status' => 'processing', 'receiver_wallet_address' => '0x3333333333333333333333333333333333333333']);

        $results2 = (new BalanceSyncService($eth))->calculateWalletBalance($wallet);
        // Two separate processing rows are both counted by the service, so total reserved = 20 + 30 + 20 = 70 -> spendable 30
        $this->assertSame('30.000000', $results2['balance']);
    }

    public function test_pending_reservation_is_deducted()
    {
        $wallet = $this->makeValidWallet('USDT');
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '60.000000', 'currency' => 'USDT', 'status' => 'pending', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $this->assertSame('40.000000', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);
    }

    public function test_completed_and_confirmed_statuses_remain_deducted()
    {
        $wallet = $this->makeValidWallet('USDT');
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '10.000000', 'currency' => 'USDT', 'status' => 'completed', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '15.000000', 'currency' => 'USDT', 'status' => 'confirmed', 'receiver_wallet_address' => '0x2222222222222222222222222222222222222222']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $this->assertSame('75.000000', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);
    }

    public function test_failed_withdrawal_releases_reservation()
    {
        $wallet = $this->makeValidWallet('USDT');
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '60.000000', 'currency' => 'USDT', 'status' => 'failed', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $this->assertSame('100.000000', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);
    }

    public function test_cancelled_or_rejected_statuses_are_not_deducted()
    {
        $wallet = $this->makeValidWallet('USDT');
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '60.000000', 'currency' => 'USDT', 'status' => 'cancelled', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '25.000000', 'currency' => 'USDT', 'status' => 'rejected', 'receiver_wallet_address' => '0x2222222222222222222222222222222222222222']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $this->assertSame('100.000000', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);
    }

    public function test_multiple_active_reservations_are_all_deducted()
    {
        $wallet = $this->makeValidWallet('USDT');
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '20.000000', 'currency' => 'USDT', 'status' => 'broadcasting', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '30.000000', 'currency' => 'USDT', 'status' => 'pending', 'receiver_wallet_address' => '0x2222222222222222222222222222222222222222']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $this->assertSame('50.000000', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);
    }

    public function test_usdt_precision_is_preserved_for_active_withdrawals()
    {
        $wallet = $this->makeValidWallet('USDT');
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '10.123456', 'currency' => 'USDT', 'status' => 'broadcasting', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);
        Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '20.000001', 'currency' => 'USDT', 'status' => 'pending', 'receiver_wallet_address' => '0x2222222222222222222222222222222222222222']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $this->assertSame('69.876543', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);
    }

    public function test_recovery_transition_keeps_broadcasting_transaction_deducted()
    {
        $wallet = $this->makeValidWallet('USDT');
        $tx = Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'type' => 'withdraw', 'amount' => '60.000000', 'currency' => 'USDT', 'status' => 'broadcasting', 'receiver_wallet_address' => '0x1111111111111111111111111111111111111111']);

        $eth = Mockery::mock(\App\Services\EthereumService::class);
        $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');

        $this->assertSame('40.000000', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);

        $tx->status = 'pending';
        $tx->save();

        $this->assertSame('40.000000', (new BalanceSyncService($eth))->calculateWalletBalance($wallet)['balance']);
    }
}
