<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
        'dark_mode',
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
            'dark_mode' => 'boolean',
            'notifications_enabled' => 'boolean',
            'notifications_2fa' => 'boolean',
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
}
