<?php

namespace App\Models;

use App\Models\Deposit;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'sender_id',
        'recipient_id',
        'type',
        'amount',
        'status',
        'reference',
        'description',
        'payment_request_id',
        'sender_wallet_address'
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class, 'tx_hash', 'reference');
    }

    public function getSenderWalletAddressAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        return $this->deposit?->sender_wallet_address;
    }

    public function getSenderNameAttribute()
    {
        if ($this->sender) {
            return $this->sender->name;
        }
        return 'نامشخص';
    }

    public function getRecipientNameAttribute()
    {
        if ($this->recipient) {
            return $this->recipient->name;
        }
        return 'نامشخص';
    }

    public function getCurrencyAttribute()
    {
        return $this->wallet->currency ?? 'UNKNOWN';
    }

    public function getMerchantNameAttribute()
    {
        if ($this->paymentRequest && $this->paymentRequest->merchant) {
            return $this->paymentRequest->merchant->name;
        }
        return null;
    }

    public function getDisplayTitleAttribute()
    {
        if ($this->type === 'transfer') {
            if ($this->merchant_name) {
                return 'پرداخت به ' . $this->merchant_name;
            }

            if ($this->recipient) {
                return 'ارسال به ' . $this->recipient->name;
            }

            return 'ارسال به گیرنده نامشخص';
        }

        if ($this->type === 'deposit') {
            if ($this->description === 'Demo Deposit') {
                return 'دریافت - ' . $this->description;
            }

            if (!empty($this->sender_wallet_address)) {
                return 'دریافت از ' . $this->sender_wallet_address;
            }

            if ($this->sender) {
                return 'دریافت از ' . $this->sender->name;
            }

            if (!empty($this->reference)) {
                return 'دریافت از تراکنش ' . substr($this->reference, 0, 10) . '...';
            }

            return 'دریافت از منبع خارجی';
        }

        return $this->description ?? 'تراکنش';
    }
}
