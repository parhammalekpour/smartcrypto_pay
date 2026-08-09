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
        return __('transactions.unknown');
    }

    public function getRecipientNameAttribute()
    {
        if ($this->recipient) {
            return $this->recipient->name;
        }
        return __('transactions.unknown');
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
                return __('transactions.payment_to_merchant', ['merchant' => $this->merchant_name]);
            }

            if ($this->recipient) {
                return __('transactions.transfer_to_recipient', ['name' => $this->recipient->name]);
            }

            return __('transactions.transfer_to_unknown');
        }

        if ($this->type === 'deposit') {
            if ($this->description === 'Demo Deposit') {
                return __('transactions.deposit_demo', ['description' => $this->description]);
            }

            if (!empty($this->sender_wallet_address)) {
                return __('transactions.deposit_from_wallet', ['address' => $this->sender_wallet_address]);
            }

            if ($this->sender) {
                return __('transactions.deposit_from_sender', ['sender' => $this->sender->name]);
            }

            if (!empty($this->reference)) {
                return __('transactions.deposit_from_transaction', ['reference' => substr($this->reference, 0, 10) . '...']);
            }

            return __('transactions.deposit_from_external');
        }

        return $this->description ?? __('transactions.default');
    }
}
