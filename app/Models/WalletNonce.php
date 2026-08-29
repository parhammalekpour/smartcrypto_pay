<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletNonce extends Model
{
    use HasFactory;

    protected $table = 'wallet_nonces';

    protected $fillable = [
        'wallet_id',
        'address',
        'next_nonce',
        'locked_at',
    ];

    protected $casts = [
        'next_nonce' => 'integer',
        'locked_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
