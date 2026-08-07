<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Customer;
use App\Models\PaymentRequest;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
        'show_balance',
        'show_transactions',
        'notifications_enabled',
        'notifications_email',
        'notifications_2fa',
        'shop_name',
        'shop_description',
        'business_email',
        'business_phone',
        'business_address',
        'website_url',
        'business_license',
        // Avatar (profile photo)
        'avatar',
        // KYC fields
        'kyc_verified',
        'kyc_documents',
        'kyc_selfie',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'show_balance' => 'boolean',
            'show_transactions' => 'boolean',
            'notifications_enabled' => 'boolean',
            'notifications_2fa' => 'boolean',
            // KYC casts
            'kyc_verified' => 'boolean',
            'kyc_documents' => 'array',
            'kyc_selfie' => 'string',
        ];
    }
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function payments()
    {
        return $this->hasMany(PaymentRequest::class, 'merchant_id')->latest();
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'merchant_id')->latest();
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function isMerchant()
    {
        return $this->role === 'merchant';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isKycVerified(): bool
    {
        return (bool) $this->kyc_verified;
    }
}
