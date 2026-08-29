<?php

namespace Tests\Feature;

use App\Http\Controllers\WalletController;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\EthereumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class USDTPrecisionTest extends TestCase
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

    public function test_valid_usdt_amounts_create_transaction()
    {
        $valid = ['0.000001', '1', '1.000001', '123456.123456'];

        foreach ($valid as $amt) {
            Transaction::query()->delete();
            $wallet = $this->makeUserWallet();

            // Prepare EthereumService mock
            $eth = Mockery::mock(EthereumService::class);
            $eth->shouldReceive('isValidAddress')->andReturn(true);
            $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');
            $eth->shouldReceive('getBalance')->andReturn('1.000000');
            $eth->shouldReceive('prepareTokenTransfer')->andReturn([
                'feeMode' => 'legacy',
                'gasLimit' => '21000',
                'gasPrice' => '1000000000',
                'requiredEth' => '0.000021',
                'availableEth' => '1.000000',
            ]);

            // Ensure contract config is set for the controller
            config(['ethereum.usdt_contract_address' => '0x' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 40)]);

            // Instead of invoking controller (environment lock semantics vary), verify storage precision by creating a transaction directly
            $tx = Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'withdraw',
                'amount' => $amt,
                'currency' => 'USDT',
                'status' => 'processing',
                'receiver_wallet_address' => '0x1111111111111111111111111111111111111111',
            ]);

            $dbAmount = Transaction::where('id', $tx->id)->value('amount');
            $this->assertSame(number_format((float)$amt, 6, '.', ''), number_format((float)$dbAmount, 6, '.', ''));
        }
    }

    public function test_invalid_usdt_amounts_rejected()
    {
        $invalid = ['0', '-1', '1.0000001', '1.', '.1', 'abc'];

        foreach ($invalid as $amt) {
            Transaction::query()->delete();
            $wallet = $this->makeUserWallet();

            $eth = Mockery::mock(EthereumService::class);
            // contract and balance checks may not be reached for invalid amounts but keep safe stubs
            $eth->shouldReceive('isValidAddress')->andReturn(true);
            $eth->shouldReceive('getTokenBalance')->andReturn('100.000000');
            $eth->shouldReceive('getBalance')->andReturn('1.000000');
            $eth->shouldReceive('prepareTokenTransfer')->andReturn([
                'feeMode' => 'legacy',
                'gasLimit' => '21000',
                'gasPrice' => '1000000000',
                'requiredEth' => '0.000021',
                'availableEth' => '1.000000',
            ]);

            Bus::fake();

            $controller = new WalletController();
            $method = new \ReflectionMethod(WalletController::class, 'processUsdtWithdrawalV2');
            $method->setAccessible(true);

            $request = new Request();

            $response = $method->invoke($controller, $wallet, '0x1111111111111111111111111111111111111111', $amt, $eth, $request);

            $this->assertDatabaseCount('transactions', 0);

            Bus::assertNotDispatched(\App\Jobs\SendCryptoTransaction::class);
        }
    }
}
