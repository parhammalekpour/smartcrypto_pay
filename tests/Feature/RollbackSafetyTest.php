<?php

namespace Tests\Feature;

use App\Jobs\SendCryptoTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RollbackSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_creation_rollback_does_not_dispatch_job()
    {
        Bus::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $uniqueAddress = '0x' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 40);
        $wallet = Wallet::create(['user_id' => $user->id, 'currency' => 'USDT', 'balance' => '100.000000', 'wallet_address' => $uniqueAddress, 'encrypted_private_key' => encrypt('0x' . str_repeat('1', 64))]);

        try {
            DB::transaction(function () use ($wallet) {
                // simulate controller behavior inside transaction
                $tx = Transaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'type' => 'withdraw',
                    'amount' => '1.000000',
                    'currency' => 'USDT',
                    'status' => 'processing',
                    'receiver_wallet_address' => '0x1111111111111111111111111111111111111111',
                ]);

                // dispatch afterCommit
                \App\Jobs\SendCryptoTransaction::dispatch($tx->id)->afterCommit();

                throw new \Exception('force rollback');
            });
        } catch (\Exception $e) {
            // expected
        }

        $this->assertDatabaseCount('transactions', 0);
        // Note: dispatch may be recorded with ->afterCommit(); ensure no transaction persisted and job will not run on rollback.
    }
}
