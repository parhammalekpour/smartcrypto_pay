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

class SignerMismatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_signer_mismatch_aborts_and_marks_failed()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a valid wallet derived from a deterministic private key
        $service = new BlockchainWalletService();
        $privateKey = '0x' . str_repeat('2', 64);
        $derivedAddress = $service->deriveAddress($privateKey, 'ETH');

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'ETH',
            'balance' => '1.000000000000000000',
            'wallet_address' => $derivedAddress,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
        ]);

        $tx = Transaction::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'type' => 'withdraw', 'amount' => '0.01', 'currency' => 'ETH', 'status' => 'processing', 'receiver_wallet_address' => '0x' . str_repeat('4', 40)]);

        $eth = Mockery::mock(EthereumService::class);
        // Introduce signer mismatch at the ETH service level (derived signer differs)
        $eth->shouldReceive('getSignerAddress')->andReturn('0x' . str_repeat('3', 40));
        $eth->shouldReceive('sendTransaction')->never();

        $job = new SendCryptoTransaction($tx->id);
        $job->handle($eth);

        $this->assertSame('failed', $tx->fresh()->status);
        $this->assertNull($tx->fresh()->tx_hash);
    }
}
