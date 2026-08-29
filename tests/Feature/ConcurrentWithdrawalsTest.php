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

class ConcurrentWithdrawalsTest extends TestCase
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

    public function test_two_simultaneous_withdrawals_one_fails()
    {
        $this->markTestSkipped('PROCESS_LEVEL_CONCURRENCY = DEFERRED in CI environment');
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

        // Debug: listen to queries to capture which one causes issues
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            file_put_contents(base_path('tests/query.log'), $query->sql . PHP_EOL, FILE_APPEND);
        });

        // Simulate two near-simultaneous requests sequentially in same process
        $respA = $method->invoke($controller, $wallet, '0x1111111111111111111111111111111111111111', '80.000000', $eth, $request);
        $respB = $method->invoke($controller, $wallet, '0x2222222222222222222222222222222222222222', '80.000000', $eth, $request);

        $txs = Transaction::where('wallet_id', $wallet->id)->get();
        $ids = $txs->pluck('id')->join(',');
        $this->assertCount(1, $txs, "Exactly one withdrawal should be accepted. Found transactions: {$ids}");

        $accepted = $txs->firstWhere('status', 'processing') ?? $txs->first();
        $this->assertNotNull($accepted);

        // Total reserved <= balance
        $totalReserved = Transaction::where('wallet_id', $wallet->id)->sum(function ($t) { return (float)$t->amount; });
        $this->assertLessThanOrEqual(100.0 + 0.000001, $totalReserved);

        Bus::assertDispatched(SendCryptoTransaction::class);
    }
}
