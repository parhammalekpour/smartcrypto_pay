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

class SequentialWithdrawalsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWallet(float $balance = 100.0): Wallet
    {
        $user = User::factory()->create();
        $uniqueAddress = '0x' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 40);
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USDT',
            'balance' => number_format($balance, 6, '.', ''),
            'wallet_address' => $uniqueAddress,
            'encrypted_private_key' => encrypt('0x' . str_repeat('1', 64)),
        ]);
        $this->actingAs($user);
        return $wallet;
    }

    public function test_sequential_withdrawals_and_precision()
    {
        $wallet = $this->makeUserWallet(100.0);

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

        // Ensure contract config
        config(['ethereum.usdt_contract_address' => '0x' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 40)]);

        Bus::fake();

        $controller = new WalletController();
        $method = new \ReflectionMethod(WalletController::class, 'processUsdtWithdrawalV2');
        $method->setAccessible(true);

        $request = new Request();

        // A = 60
        $method->invoke($controller, $wallet, '0x1111111111111111111111111111111111111111', '60.000000', $eth, $request);
        // B = 40
        $method->invoke($controller, $wallet, '0x2222222222222222222222222222222222222222', '40.000000', $eth, $request);

        $txs = Transaction::where('wallet_id', $wallet->id)->get();
        $this->assertCount(2, $txs);

        $total = $txs->sum('amount');
        $this->assertEquals('100.000000', number_format($total, 6, '.', ''));

        // C = 0.000001 should be rejected (no available remaining balance)
        $result = $method->invoke($controller, $wallet, '0x3333333333333333333333333333333333333333', '0.000001', $eth, $request);
        // No additional transaction should be created beyond the two earlier
        $this->assertDatabaseCount('transactions', 2);
    }
}
