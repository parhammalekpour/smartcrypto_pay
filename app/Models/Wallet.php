<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use App\Services\BlockchainWalletService;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'owner_type',
        'owner_id',
        'wallet_address',
        'encrypted_private_key',
        'network',
        'currency',
        'balance',
        'last_scanned_block'
    ];

    protected $casts = [
        'balance' => 'string',
        'last_scanned_block' => 'integer'
    ];

    /**
     * When creating a wallet, if no wallet_address exists, generate an HD wallet
     * using the BlockchainWalletService and encrypt the private key using
     * Laravel's Crypt facade before persisting.
     *
     * This keeps controller changes minimal — wallets created via existing
     * code paths (Wallet::create([...])) will get HD wallets automatically.
     */
    protected static function booted()
    {
        static::creating(function (Wallet $wallet) {
            // If a wallet address already provided, don't auto-generate
            if (!empty($wallet->wallet_address) || !empty($wallet->encrypted_private_key)) {
                return;
            }

            // Determine currency default
            $currency = $wallet->currency ?? 'ETH';

            $service = new BlockchainWalletService();

            try {
                $res = $service->generateHdWallet($currency);

                $address = $res['address'] ?? null;
                $privateKey = $res['privateKey'] ?? null;

                if ($address && $privateKey) {
                    $wallet->wallet_address = $address;
                    // Encrypt private key using Laravel encryption
                    $wallet->encrypted_private_key = Crypt::encryptString($privateKey);

                    // Default network mapping (can be extended later)
                    $wallet->network = match(strtoupper($currency)) {
                        'BTC' => 'bitcoin',
                        default => 'ethereum'
                    };

                    // If user_id is set, populate owner fields for compatibility
                    if (!empty($wallet->user_id)) {
                        $wallet->owner_type = 'User';
                        $wallet->owner_id = $wallet->user_id;
                    }
                }
            } catch (\Throwable $e) {
                // On failure, do not block creation — surface an error in logs
                // Controllers may choose to validate / retry as needed.
                \Log::error('Failed to auto-generate HD wallet: ' . $e->getMessage());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Helper to decrypt private key in memory when absolutely needed.
     * Use with extreme care and do not expose decrypted key to logs.
     */
    public function getPrivateKey(): ?string
    {
        if (empty($this->encrypted_private_key)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_private_key);
        } catch (\Throwable $e) {
            \Log::error('Failed to decrypt wallet private key: ' . $e->getMessage());
            return null;
        }
    }
}