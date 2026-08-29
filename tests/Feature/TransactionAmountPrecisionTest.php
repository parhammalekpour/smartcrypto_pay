<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Services\BlockchainWalletService;
use App\Services\EthereumService;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionAmountPrecisionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }
    protected function createEthWallet(string $privateKey)
    {
        $service = new BlockchainWalletService();
        $derived = $service->deriveAddress($privateKey, 'ETH');

        $wallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => 'ETH',
            'wallet_address' => $derived,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
            'balance' => '0'
        ]);

        return $wallet;
    }

    protected function createUsdtWallet(string $privateKey)
    {
        // USDT does not enforce derived-address check in Wallet::booted for non-ETH currencies
        $addr = '0x' . str_repeat('9', 40);

        $wallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => 'USDT',
            'wallet_address' => $addr,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
            'balance' => '0'
        ]);

        return $wallet;
    }

    public function test_eth_normal_and_scaled_amounts()
    {
        $pk = '0x' . str_repeat('1', 64);
        $wallet = $this->createEthWallet($pk);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '1',
            'currency' => 'ETH',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('2', 40)
        ]);

        $this->assertEquals('1.000000000000000000', $tx->amount);

        $tx2 = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '0.100000000000000000',
            'currency' => 'ETH',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('3', 40)
        ]);

        $this->assertEquals('0.100000000000000000', $tx2->amount);

        $tx3 = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '0.000000000000000001',
            'currency' => 'ETH',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('4', 40)
        ]);

        $this->assertEquals('0.000000000000000001', $tx3->amount);
    }

    public function test_usdt_normal_and_scaled_amounts()
    {
        $pk = '0x' . str_repeat('2', 64);
        $wallet = $this->createUsdtWallet($pk);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '1',
            'currency' => 'USDT',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('5', 40)
        ]);

        $this->assertEquals('1.000000', $tx->amount);

        $tx2 = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '1.250000',
            'currency' => 'USDT',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('6', 40)
        ]);

        $this->assertEquals('1.250000', $tx2->amount);

        // Ensure USDT does NOT return 18 decimals
        $this->assertFalse(str_ends_with($tx2->amount, '000000000000000000'));
        $this->assertEquals(6, strlen(explode('.', $tx2->amount)[1]));
    }

    public function test_scientific_notation_eth_and_usdt()
    {
        $pk1 = '0x' . str_repeat('3', 64);
        $ethWallet = $this->createEthWallet($pk1);

        $txEth = Transaction::create([
            'wallet_id' => $ethWallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '1.0E-18',
            'currency' => 'ETH',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('7', 40)
        ]);

        $this->assertEquals('0.000000000000000001', $txEth->amount);

        $pk2 = '0x' . str_repeat('4', 64);
        $usdtWallet = $this->createUsdtWallet($pk2);

        $txUsdt = Transaction::create([
            'wallet_id' => $usdtWallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '1.0E-6',
            'currency' => 'USDT',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('8', 40)
        ]);

        $this->assertEquals('0.000001', $txUsdt->amount);
    }

    public function test_unknown_currency_preserves_value_and_normalizes_scientific()
    {
        $pk = '0x' . str_repeat('5', 64);
        // create a generic ETH wallet to attach; currency on tx will be null/unknown
        $wallet = $this->createEthWallet($pk);

        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '1.2345',
            'currency' => null,
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('f', 40)
        ]);

        $this->assertEquals('1.2345', $tx->amount);

        $txSci = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => '1.0E-3',
            'currency' => null,
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('e', 40)
        ]);

        $this->assertEquals('0.001', $txSci->amount);
    }

    public function test_eth_amount_normalization_supports_locale_and_scientific_input()
    {
        $this->assertSame('0.0001', EthereumService::normalizeHumanAmountInput('0,0001'));
        $this->assertSame('0.001', EthereumService::normalizeHumanAmountInput('1e-3'));
        $this->assertSame('0.000000000000000001', EthereumService::normalizeHumanAmountInput('1e-18'));
        $this->assertSame('1234.5678', EthereumService::normalizeHumanAmountInput('1.234,5678'));
    }

    public function test_database_storage_remains_unchanged()
    {
        $pk = '0x' . str_repeat('6', 64);
        $wallet = $this->createUsdtWallet($pk);

        $original = '1.0E-6';
        $tx = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => $original,
            'currency' => 'USDT',
            'status' => 'processing',
            'receiver_wallet_address' => '0x' . str_repeat('1', 40)
        ]);

        $raw = DB::table('transactions')->where('id', $tx->id)->value('amount');
        $this->assertEquals($original, (string)$raw);

        // But accessor returns normalized value for USDT
        $this->assertEquals('0.000001', $tx->amount);

        // JSON serialization exposes accessor result
        $arr = $tx->toArray();
        $this->assertEquals('0.000001', $arr['amount']);
    }
}
