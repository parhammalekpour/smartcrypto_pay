<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Services\BlockchainWalletService;

class Wallet extends Model
{
    use HasFactory;

    private ?string $cachedPrivateKey = null;

    protected $fillable = [
        'user_id',
        'owner_type',
        'owner_id',
        'wallet_address',
        'encrypted_private_key',
        'network',
        'currency',
        'name',
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
            $currency = strtoupper($wallet->currency ?? 'ETH');
            $service = new BlockchainWalletService();

            $hasAddress = !empty($wallet->wallet_address);
            $hasEncryptedKey = !empty($wallet->encrypted_private_key);

            if ($hasAddress || $hasEncryptedKey) {
                if (!$hasAddress || !$hasEncryptedKey) {
                    throw new \RuntimeException('Wallet creation requires both wallet_address and encrypted_private_key or neither.');
                }

                if ($currency === 'ETH') {
                    if (!$service->isValidAddress($wallet->wallet_address, 'ETH')) {
                        throw new \RuntimeException('Invalid Ethereum wallet address');
                    }

                    try {
                        $decryptedKey = Crypt::decryptString($wallet->encrypted_private_key);
                    } catch (\Throwable $e) {
                        Log::error('Failed to decrypt provided encrypted private key during wallet creation', [
                            'wallet_address' => $wallet->wallet_address,
                            'currency' => $currency,
                            'error_message' => $e->getMessage(),
                            'error_name' => get_class($e),
                        ]);
                        throw new \RuntimeException('Encrypted private key could not be decrypted during wallet creation.');
                    }

                    $normalizedPrivateKey = self::normalizePrivateKey($decryptedKey);
                    if ($normalizedPrivateKey === null) {
                        throw new \RuntimeException('Decrypted private key format is invalid.');
                    }

                    $wallet->encrypted_private_key = Crypt::encryptString($normalizedPrivateKey);

                    $derivedAddress = $service->deriveAddress($normalizedPrivateKey, 'ETH');
                    if (strtolower($derivedAddress) !== strtolower($wallet->wallet_address)) {
                        throw new \RuntimeException('Provided encrypted private key does not derive the provided wallet address.');
                    }
                }

                if (empty($wallet->network)) {
                    $wallet->network = match($currency) {
                        'BTC' => 'bitcoin',
                        default => 'ethereum',
                    };
                }

                if (!empty($wallet->user_id)) {
                    $wallet->owner_type = 'User';
                    $wallet->owner_id = $wallet->user_id;
                }

                return;
            }

            try {
                $res = $service->generateHdWallet($currency);

                $address = $res['address'] ?? null;
                $privateKey = $res['privateKey'] ?? null;

                if (empty($address) || empty($privateKey)) {
                    throw new \RuntimeException('HD wallet generation returned incomplete data.');
                }

                if ($currency === 'ETH') {
                    if (!$service->isValidAddress($address, 'ETH')) {
                        throw new \RuntimeException('Generated Ethereum wallet address is invalid.');
                    }

                    $normalizedPrivateKey = self::normalizePrivateKey($privateKey);
                    if ($normalizedPrivateKey === null) {
                        throw new \RuntimeException('Generated private key format is invalid.');
                    }

                    $derivedAddress = $service->deriveAddress($normalizedPrivateKey, 'ETH');
                    if (strtolower($derivedAddress) !== strtolower($address)) {
                        throw new \RuntimeException('Generated private key does not derive the generated wallet address.');
                    }
                } else {
                    $normalizedPrivateKey = self::normalizePrivateKey($privateKey);
                    if ($normalizedPrivateKey === null) {
                        throw new \RuntimeException('Generated private key format is invalid.');
                    }
                }

                $wallet->wallet_address = $address;
                $wallet->encrypted_private_key = Crypt::encryptString($normalizedPrivateKey);
                $wallet->network = match($currency) {
                    'BTC' => 'bitcoin',
                    default => 'ethereum',
                };

                if (!empty($wallet->user_id)) {
                    $wallet->owner_type = 'User';
                    $wallet->owner_id = $wallet->user_id;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to generate wallet during Wallet::creating event: ' . $e->getMessage(), [
                    'currency' => $currency,
                    'wallet_user_id' => $wallet->user_id,
                ]);
                throw $e;
            }
        });

        static::saving(function (Wallet $wallet) {
            if (!$wallet->isDirty(['wallet_address', 'encrypted_private_key'])) {
                return;
            }

            $currency = strtoupper($wallet->currency ?? 'ETH');
            $hasAddress = !empty($wallet->wallet_address);
            $hasEncryptedKey = !empty($wallet->encrypted_private_key);

            if ($hasAddress xor $hasEncryptedKey) {
                throw new \RuntimeException('Wallet storage requires both wallet_address and encrypted_private_key to be present together.');
            }

            if ($hasAddress && $hasEncryptedKey && $currency === 'ETH') {
                $service = new BlockchainWalletService();

                if (!$service->isValidAddress($wallet->wallet_address, 'ETH')) {
                    throw new \RuntimeException('Invalid Ethereum wallet address');
                }

                try {
                    $decryptedKey = Crypt::decryptString($wallet->encrypted_private_key);
                } catch (\Throwable $e) {
                    Log::error('Failed to decrypt provided encrypted private key during wallet save', [
                        'wallet_address' => $wallet->wallet_address,
                        'currency' => $currency,
                        'error_message' => $e->getMessage(),
                        'error_name' => get_class($e),
                    ]);
                    throw new \RuntimeException('Encrypted private key could not be decrypted during wallet save.');
                }

                $normalizedPrivateKey = self::normalizePrivateKey($decryptedKey);
                if ($normalizedPrivateKey === null) {
                    throw new \RuntimeException('Decrypted private key format is invalid.');
                }

                if (strtolower($service->deriveAddress($normalizedPrivateKey, 'ETH')) !== strtolower($wallet->wallet_address)) {
                    throw new \RuntimeException('Provided encrypted private key does not derive the provided wallet address.');
                }

                $wallet->encrypted_private_key = Crypt::encryptString($normalizedPrivateKey);
            }
        });
    }

    protected static function normalizePrivateKey(?string $privateKey): ?string
    {
        if (!is_string($privateKey)) {
            return null;
        }

        $trimmed = trim($privateKey);
        if (str_starts_with($trimmed, '0x') || str_starts_with($trimmed, '0X')) {
            $trimmed = substr($trimmed, 2);
        }

        if (!preg_match('/^[0-9a-fA-F]{64}$/', $trimmed)) {
            return null;
        }

        return '0x' . strtolower($trimmed);
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
        if ($this->cachedPrivateKey !== null) {
            return $this->cachedPrivateKey;
        }

        if (empty($this->encrypted_private_key)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($this->encrypted_private_key);
            $normalized = self::normalizePrivateKey($decrypted);
            if ($normalized === null) {
                Log::error('Decrypted wallet private key is malformed or invalid.', [
                    'wallet_id' => $this->id,
                    'wallet_address' => $this->wallet_address,
                    'decrypted_length' => is_string($decrypted) ? strlen($decrypted) : 0,
                ]);
                $this->cachedPrivateKey = null;
                return null;
            }

            if (strtoupper($this->currency) === 'ETH' && !empty($this->wallet_address)) {
                $service = new BlockchainWalletService();
                try {
                    $derivedAddress = $service->deriveAddress($normalized, 'ETH');
                } catch (\Throwable $e) {
                    Log::error('Failed to derive wallet address from decrypted private key.', [
                        'wallet_id' => $this->id,
                        'wallet_address' => $this->wallet_address,
                        'error_message' => $e->getMessage(),
                        'error_name' => get_class($e),
                    ]);
                    $this->cachedPrivateKey = null;
                    return null;
                }

                if (strtolower($derivedAddress) !== strtolower($this->wallet_address)) {
                    Log::error('Wallet private key does not derive the stored wallet address.', [
                        'wallet_id' => $this->id,
                        'wallet_address' => $this->wallet_address,
                        'derived_address' => $derivedAddress,
                    ]);
                    $this->cachedPrivateKey = null;
                    return null;
                }
            }

            $this->cachedPrivateKey = $normalized;
            return $this->cachedPrivateKey;
        } catch (\Throwable $e) {
            Log::error('Failed to decrypt wallet private key: ' . $e->getMessage(), [
                'wallet_id' => $this->id,
                'wallet_address' => $this->wallet_address,
            ]);
            $this->cachedPrivateKey = null;
            return null;
        }
    }

    public function hasSigningKey(): bool
    {
        return $this->getPrivateKey() !== null;
    }

    public function isLegacyIncomplete(): bool
    {
        return empty($this->wallet_address) || empty($this->encrypted_private_key);
    }
}
