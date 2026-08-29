<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'merchant_id',
        'currency',
        'amount',
        'tx_hash',
        'sender_wallet_address',
        'block_number',
        'block_hash',
        'transaction_index',
        'receipt_status',
        'status',
        'confirmations',
        'confirmed_at',
        'processed_at',
        'reorged_at',
        'reorg_reason',
        'canonical_checked_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'reorged_at' => 'datetime',
        'canonical_checked_at' => 'datetime',
        'transaction_index' => 'integer',
        'confirmations' => 'integer',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
