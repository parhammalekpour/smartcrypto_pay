<?php

namespace Tests\Feature;

use App\Jobs\UpdateDepositConfirmationsJob;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BalanceSyncService;
use App\Services\BlockchainWalletService;
use App\Services\EthereumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ConfirmationReceiptFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeWallet(string $suffix = ''): Wallet
    {
        $service = new BlockchainWalletService();
        $privateKey = '0x' . bin2hex(random_bytes(32));
        if ($suffix !== '') {
            $privateKey = '0x' . substr(hash('sha256', $suffix . random_bytes(16)), 0, 64);
        }
        $address = $service->deriveAddress($privateKey, 'ETH');

        return Wallet::create([
            'user_id' => User::factory()->create()->id,
            'currency' => 'USDT',
            'balance' => '100.000000',
            'wallet_address' => $address,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
        ]);
    }

    public function test_receipt_status_zero_is_reverted(): void
    {
        $this->assertFalse((new EthereumService())->normalizeReceiptStatus(0));
        $this->assertFalse((new EthereumService())->normalizeReceiptStatus('0'));
        $this->assertFalse((new EthereumService())->normalizeReceiptStatus(false));
        $this->assertFalse((new EthereumService())->normalizeReceiptStatus('0x0'));
        $this->assertFalse((new EthereumService())->normalizeReceiptStatus('0X0'));
    }

    public function test_unknown_and_missing_receipt_status_is_not_confirmed(): void
    {
        $service = new EthereumService();
        $this->assertNull($service->normalizeReceiptStatus(null));
        $this->assertNull($service->normalizeReceiptStatus('0x2'));
        $this->assertNull($service->normalizeReceiptStatus('unknown'));
    }

    public function test_receipt_status_one_is_success(): void
    {
        $this->assertTrue((new EthereumService())->normalizeReceiptStatus(1));
        $this->assertTrue((new EthereumService())->normalizeReceiptStatus('1'));
        $this->assertTrue((new EthereumService())->normalizeReceiptStatus(true));
        $this->assertTrue((new EthereumService())->normalizeReceiptStatus('0x1'));
        $this->assertTrue((new EthereumService())->normalizeReceiptStatus('0X1'));
    }

    public function test_unknown_receipt_status_keeps_deposit_pending(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('11');

        $deposit = Deposit::create([
            'wallet_id' => $wallet->id,
            'currency' => 'USDT',
            'amount' => '10.000000',
            'tx_hash' => '0xunknownstatus_01',
            'block_number' => 10,
            'status' => 'pending',
            'confirmations' => 0,
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'deposit',
            'amount' => '10.000000',
            'currency' => 'USDT',
            'status' => 'pending',
            'reference' => '0xunknownstatus_01',
            'description' => 'deposit',
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(12);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xunknownstatus_01')->andReturn([
            'receipt' => ['status' => '0x2'],
            'confirmations' => 3,
        ]);
        $eth->shouldReceive('normalizeReceiptStatus')->once()->with('0x2')->andReturn(null);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->never();

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $this->assertSame('pending', $deposit->fresh()->status);
        $this->assertSame('pending', Transaction::where('reference', '0xunknownstatus_01')->first()->status);
    }

    public function test_address_validation_does_not_require_network_lookup(): void
    {
        $service = Mockery::mock(EthereumService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('runNode')->never();

        $this->assertTrue($service->isValidAddress('0x0000000000000000000000000000000000000001'));
        $this->assertTrue($service->isValidAddress('0xAbCDEF1234567890ABCDEF1234567890ABCDEF12'));
        $this->assertFalse($service->isValidAddress(''));
        $this->assertFalse($service->isValidAddress('0x1234'));
        $this->assertFalse($service->isValidAddress('not-an-address'));
    }

    public function test_deposit_with_successful_receipt_and_enough_confirmations_is_confirmed(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('22');

        $deposit = Deposit::create([
            'wallet_id' => $wallet->id,
            'currency' => 'USDT',
            'amount' => '10.000000',
            'tx_hash' => '0xsuccessdeposit_01',
            'block_number' => 9,
            'status' => 'pending',
            'confirmations' => 0,
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'deposit',
            'amount' => '10.000000',
            'currency' => 'USDT',
            'status' => 'pending',
            'reference' => '0xsuccessdeposit_01',
            'description' => 'deposit',
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(12);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xsuccessdeposit_01')->andReturn([
            'receipt' => ['status' => '0x1', 'blockNumber' => '0x9', 'blockHash' => '0xblock-9'],
            'confirmations' => 4,
        ]);
        $eth->shouldReceive('normalizeReceiptStatus')->once()->with('0x1')->andReturn(true);
        $eth->shouldReceive('getBlock')->once()->with(9)->andReturn(['hash' => '0xblock-9']);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->once()->andReturn(['balance' => '100.000000']);

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $this->assertSame('confirmed', $deposit->fresh()->status);
        $this->assertSame('confirmed', Transaction::where('reference', '0xsuccessdeposit_01')->first()->status);
    }

    public function test_block_action_name_is_used_for_canonical_block_lookup(): void
    {
        $service = Mockery::mock(EthereumService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('runNode')->once()->with(['block', '11556565'])->andReturn([
            'block' => ['number' => 11556565, 'hash' => '0xblock-11556565', 'timestamp' => 1710000000, 'transactionHashes' => ['0xabc']],
            'number' => 11556565,
            'hash' => '0xblock-11556565',
            'timestamp' => 1710000000,
            'transactionHashes' => ['0xabc'],
        ]);

        $result = $service->getBlock(11556565);

        $this->assertSame('0xblock-11556565', $result['hash']);
        $this->assertSame(['0xabc'], $result['transactionHashes']);
    }

    public function test_deposit_with_successful_receipt_persists_block_metadata_and_confirms(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('blockmeta');

        $deposit = Deposit::create([
            'wallet_id' => $wallet->id,
            'currency' => 'ETH',
            'amount' => '0.000100',
            'tx_hash' => '0xblockmeta_01',
            'block_number' => 11556565,
            'status' => 'pending',
            'confirmations' => 0,
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'deposit',
            'amount' => '0.000100',
            'currency' => 'ETH',
            'status' => 'pending',
            'reference' => '0xblockmeta_01',
            'description' => 'deposit',
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(11556568);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xblockmeta_01')->andReturn([
            'receipt' => [
                'status' => '0x1',
                'blockNumber' => '0x' . dechex(11556565),
                'blockHash' => '0x3e1c37b0d58d9792a5f7c65d72030534397ad58403d447dbb45a2fb6827142c7',
                'transactionIndex' => '0x4',
            ],
            'confirmations' => 4,
        ]);
        $eth->shouldReceive('normalizeReceiptStatus')->once()->with('0x1')->andReturn(true);
        $eth->shouldReceive('getBlock')->once()->with(11556565)->andReturn([
            'block' => ['hash' => '0x3e1c37b0d58d9792a5f7c65d72030534397ad58403d447dbb45a2fb6827142c7', 'number' => 11556565, 'timestamp' => 1710000000],
            'hash' => '0x3e1c37b0d58d9792a5f7c65d72030534397ad58403d447dbb45a2fb6827142c7',
            'number' => 11556565,
            'timestamp' => 1710000000,
            'transactionHashes' => ['0x4807cec1f16c5fbc6650e8e08c23c8c3b6effa1e4de8ef1c5a245db9d5023919'],
        ]);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->once()->andReturn(['balance' => '0.000000']);

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $freshDeposit = $deposit->fresh();
        $this->assertSame('confirmed', $freshDeposit->status);
        $this->assertSame('0x3e1c37b0d58d9792a5f7c65d72030534397ad58403d447dbb45a2fb6827142c7', $freshDeposit->block_hash);
        $this->assertSame(4, (int) $freshDeposit->transaction_index);
        $this->assertSame('1', $freshDeposit->receipt_status);
        $this->assertNotNull($freshDeposit->confirmed_at);
    }

    public function test_scan_once_persists_tx_hash_metadata_for_deposit_transaction(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $user = User::factory()->create();
        $ethService = new BlockchainWalletService();
        $privateKey = '0x' . bin2hex(random_bytes(32));
        $walletAddress = $ethService->deriveAddress($privateKey, 'ETH');

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'ETH',
            'balance' => '0.000000',
            'wallet_address' => $walletAddress,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
            'last_scanned_block' => 11555950,
        ]);

        $txHash = '0x' . bin2hex(random_bytes(32));

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(11555951);
        $eth->shouldReceive('getTransactionHistory')->once()->with($wallet->wallet_address, 20, 11555951)->andReturn([
            [
                'hash' => $txHash,
                'from' => '0x360Fd699e7BF73383552fE5A8642D549489A53F9',
                'to' => $wallet->wallet_address,
                'value' => '0.001',
                'blockNumber' => '11555951',
            ],
        ]);

        $service = new \App\Services\BlockchainDepositService($eth);
        $service->scanOnce(20, $wallet->id, null, null);

        $deposit = Deposit::where('tx_hash', $txHash)->first();
        $transaction = Transaction::where('tx_hash', $txHash)->where('type', 'deposit')->first();

        $this->assertNotNull($deposit);
        $this->assertNotNull($transaction);
        $this->assertSame('pending', $deposit->status);
        $this->assertSame($txHash, $transaction->tx_hash);
        $this->assertSame(11555951, (int) $transaction->block_number);
        $this->assertGreaterThan(0, (int) $transaction->confirmations);
        $this->assertSame($wallet->wallet_address, $transaction->receiver_wallet_address);

        $jobEth = Mockery::mock(EthereumService::class);
        $jobEth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(11555960);
        $jobEth->shouldReceive('getTransactionReceipt')->once()->with($txHash)->andReturn([
            'receipt' => ['status' => '0x1', 'blockNumber' => '0x' . dechex(11555951), 'blockHash' => '0xblock-11555951'],
            'confirmations' => 10,
        ]);
        $jobEth->shouldReceive('normalizeReceiptStatus')->once()->with('0x1')->andReturn(true);
        $jobEth->shouldReceive('getBlock')->once()->with(11555951)->andReturn(['hash' => '0xblock-11555951']);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->once()->with(Mockery::on(fn ($walletInstance) => $walletInstance->id === $wallet->id))->andReturn(['balance' => '0.000000']);

        (new UpdateDepositConfirmationsJob())->handle($jobEth, $balanceService);

        $this->assertSame('confirmed', $deposit->fresh()->status);
        $this->assertSame('confirmed', $transaction->fresh()->status);
    }

    public function test_deposit_with_reverted_receipt_is_not_confirmed(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('33');

        $deposit = Deposit::create([
            'wallet_id' => $wallet->id,
            'currency' => 'USDT',
            'amount' => '10.000000',
            'tx_hash' => '0xreverteddeposit_02',
            'block_number' => 9,
            'status' => 'pending',
            'confirmations' => 0,
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'deposit',
            'amount' => '10.000000',
            'currency' => 'USDT',
            'status' => 'pending',
            'reference' => '0xreverteddeposit_02',
            'description' => 'deposit',
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(12);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xreverteddeposit_02')->andReturn([
            'receipt' => ['status' => '0x0', 'blockNumber' => '0x9'],
            'confirmations' => 4,
        ]);
        $eth->shouldReceive('normalizeReceiptStatus')->once()->with('0x0')->andReturn(false);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->never();

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $this->assertSame('pending', $deposit->fresh()->status);
        $this->assertSame('pending', Transaction::where('reference', '0xreverteddeposit_02')->first()->status);
    }

    public function test_withdrawal_successful_receipt_and_enough_confirmations_marks_completed(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('44');

        $withdrawal = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '10.000000',
            'currency' => 'USDT',
            'status' => 'pending',
            'reference' => null,
            'description' => 'USDT Sepolia send',
            'sender_wallet_address' => $wallet->wallet_address,
            'receiver_wallet_address' => '0xabcdefabcdefabcdefabcdefabcdefabcdefabcd',
            'tx_hash' => '0xsuccesswithdraw_03',
            'block_number' => null,
            'confirmations' => 0,
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(12);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xsuccesswithdraw_03')->andReturn([
            'receipt' => ['status' => '0x1', 'blockNumber' => '0x9', 'blockHash' => '0xwithdraw-block'],
            'confirmations' => 4,
        ]);
        $eth->shouldReceive('normalizeReceiptStatus')->once()->with('0x1')->andReturn(true);
        $eth->shouldReceive('getBlock')->once()->with(9)->andReturn(['hash' => '0xwithdraw-block']);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->once()->andReturn(['balance' => '90.000000']);

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $this->assertSame('completed', $withdrawal->fresh()->status);
    }

    public function test_withdrawal_reverted_receipt_marks_failed_and_syncs_balance(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('55');

        $withdrawal = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '10.000000',
            'currency' => 'USDT',
            'status' => 'pending',
            'reference' => null,
            'description' => 'USDT Sepolia send',
            'sender_wallet_address' => $wallet->wallet_address,
            'receiver_wallet_address' => '0xfeedfeedfeedfeedfeedfeedfeedfeedfeedfeedfeed',
            'tx_hash' => '0xrevertedwithdraw_04',
            'block_number' => null,
            'confirmations' => 0,
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(12);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xrevertedwithdraw_04')->andReturn([
            'receipt' => ['status' => '0x0', 'blockNumber' => '0x9'],
            'confirmations' => 4,
        ]);
        $eth->shouldReceive('normalizeReceiptStatus')->once()->with('0x0')->andReturn(false);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->once()->andReturn(['balance' => '100.000000']);

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $this->assertSame('failed', $withdrawal->fresh()->status);
    }

    public function test_null_receipt_keeps_withdrawal_pending(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('66');

        $withdrawal = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '10.000000',
            'currency' => 'USDT',
            'status' => 'pending',
            'reference' => null,
            'description' => 'USDT Sepolia send',
            'sender_wallet_address' => $wallet->wallet_address,
            'receiver_wallet_address' => '0x1234123412341234123412341234123412341234',
            'tx_hash' => '0xnullreceipt_05',
            'block_number' => null,
            'confirmations' => 0,
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(12);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xnullreceipt_05')->andReturn(['receipt' => null]);

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->never();

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    public function test_receipt_lookup_exception_keeps_withdrawal_pending(): void
    {
        config()->set('ethereum.confirmation_threshold', 2);

        $wallet = $this->makeWallet('77');

        $withdrawal = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => 'withdraw',
            'amount' => '10.000000',
            'currency' => 'USDT',
            'status' => 'pending',
            'reference' => null,
            'description' => 'USDT Sepolia send',
            'sender_wallet_address' => $wallet->wallet_address,
            'receiver_wallet_address' => '0x9876987698769876987698769876987698769876',
            'tx_hash' => '0xrpcfail_06',
            'block_number' => null,
            'confirmations' => 0,
        ]);

        $eth = Mockery::mock(EthereumService::class);
        $eth->shouldReceive('getCurrentBlockNumber')->once()->andReturn(12);
        $eth->shouldReceive('getTransactionReceipt')->once()->with('0xrpcfail_06')->andThrow(new RuntimeException('RPC unavailable'));

        $balanceService = Mockery::mock(BalanceSyncService::class);
        $balanceService->shouldReceive('syncWallet')->never();

        (new UpdateDepositConfirmationsJob())->handle($eth, $balanceService);

        $this->assertSame('pending', $withdrawal->fresh()->status);
    }
}
