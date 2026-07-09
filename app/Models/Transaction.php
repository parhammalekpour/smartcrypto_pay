<?php

namespace App\Models;

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
        'payment_request_id'
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
}